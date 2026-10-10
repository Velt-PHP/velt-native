package com.velt.nativeapp

import java.util.UUID
import java.util.concurrent.ConcurrentHashMap
import java.util.concurrent.ExecutorService
import java.util.concurrent.Executors
import java.util.concurrent.Future
import java.util.concurrent.ScheduledExecutorService
import java.util.concurrent.TimeUnit
import java.util.concurrent.atomic.AtomicBoolean
import kotlinx.serialization.Serializable
import kotlinx.serialization.SerialName
import kotlinx.serialization.json.Json
import kotlinx.serialization.json.JsonElement
import kotlinx.serialization.json.JsonObject
import kotlinx.serialization.json.buildJsonObject
import kotlinx.serialization.json.put

@Serializable
data class NativePhpErrorEnvelope(
    val code: String,
    val message: String,
)

@Serializable
data class NativePhpResponseEnvelope(
    @SerialName("request_id")
    val requestId: String,
    val status: String,
    val result: JsonElement? = null,
    val error: NativePhpErrorEnvelope? = null,
)

interface NativePhpInvoker {
    fun call(request: String): String

    fun cancel(requestId: String): Boolean
}

fun interface NativePhpLibraryLoader {
    fun load(libraryName: String)
}

open class NativePhpTransportException(message: String, cause: Throwable? = null) : RuntimeException(message, cause)

class NativePhpLibraryUnavailableException(message: String, cause: Throwable? = null) :
    NativePhpTransportException(message, cause)

class NativePhpInvalidResponseException(message: String, cause: Throwable? = null) :
    NativePhpTransportException(message, cause)

class NativePhpPayloadException(message: String, cause: Throwable? = null) :
    NativePhpTransportException(message, cause)

open class NativePhpErrorException(
    val code: String,
    message: String,
) : NativePhpTransportException(message)

class NativePhpCapabilityException(message: String) : NativePhpErrorException("CAPABILITY_UNKNOWN", message)

class NativePhpTimeoutException(val requestId: String, message: String) : NativePhpTransportException(message)

class NativePhpCancelledException(val requestId: String, message: String) : NativePhpTransportException(message)

class NativePhpInterruptedException(val requestId: String, message: String, cause: Throwable? = null) :
    NativePhpTransportException(message, cause)

interface NativePhpTransport : AutoCloseable {
    fun call(
        payload: String,
        timeoutMillis: Long = DEFAULT_TIMEOUT_MILLIS,
        callback: (Result<NativePhpResponseEnvelope>) -> Unit,
    ): String

    fun cancel(requestId: String): Boolean

    companion object {
        const val DEFAULT_TIMEOUT_MILLIS = 30_000L
    }
}

/**
 * Asynchronous Kotlin boundary for the NativePHP host.
 *
 * The host must provide the `nativephpCall` and `nativephpCancel` JNI symbols
 * through the explicitly loaded NativePHP library. There is no HTML, fake or
 * silent fallback in this production implementation.
 */
class JniNativePhpTransport(
    libraryName: String = DEFAULT_LIBRARY_NAME,
    libraryLoader: NativePhpLibraryLoader = NativePhpLibraryLoader { System.loadLibrary(it) },
    private val invoker: NativePhpInvoker = JniNativePhpInvoker(),
    private val worker: ExecutorService = Executors.newCachedThreadPool(),
    private val scheduler: ScheduledExecutorService = Executors.newScheduledThreadPool(1),
) : NativePhpTransport {
    private val json = Json { ignoreUnknownKeys = false; explicitNulls = true }
    private val pending = ConcurrentHashMap<String, PendingCall>()
    private val closed = AtomicBoolean(false)

    init {
        try {
            libraryLoader.load(libraryName)
        } catch (error: UnsatisfiedLinkError) {
            throw NativePhpLibraryUnavailableException(
                "NativePHP JNI library '$libraryName' could not be loaded.",
                error,
            )
        }
    }

    override fun call(
        payload: String,
        timeoutMillis: Long,
        callback: (Result<NativePhpResponseEnvelope>) -> Unit,
    ): String {
        check(!closed.get()) { "NativePHP transport is closed." }
        require(timeoutMillis > 0) { "timeoutMillis must be greater than zero." }

        val requestId = UUID.randomUUID().toString()
        val request = try {
            buildRequest(requestId, payload)
        } catch (error: Exception) {
            throw NativePhpPayloadException("The PHP payload must be a valid JSON object.", error)
        }
        val call = PendingCall(requestId, callback)
        pending[requestId] = call

        call.timeout = scheduler.schedule(
            { failTimeout(call, timeoutMillis) },
            timeoutMillis,
            TimeUnit.MILLISECONDS,
        )
        call.future = worker.submit {
            try {
                val response = decodeResponse(requestId, invoker.call(request))
                complete(call, Result.success(response))
            } catch (error: InterruptedException) {
                Thread.currentThread().interrupt()
                complete(
                    call,
                    Result.failure(
                        NativePhpInterruptedException(requestId, "NativePHP request was interrupted.", error),
                    ),
                )
            } catch (error: UnsatisfiedLinkError) {
                complete(
                    call,
                    Result.failure(
                        NativePhpLibraryUnavailableException(
                            "NativePHP JNI symbol nativephpCall is unavailable.",
                            error,
                        ),
                    ),
                )
            } catch (error: NativePhpTransportException) {
                complete(call, Result.failure(error))
            } catch (error: Throwable) {
                complete(call, Result.failure(NativePhpTransportException("NativePHP call failed.", error)))
            }
        }
        return requestId
    }

    override fun cancel(requestId: String): Boolean {
        val call = pending[requestId] ?: return false
        if (!call.completed.compareAndSet(false, true)) {
            return false
        }
        call.future?.cancel(true)
        runCatching { invoker.cancel(requestId) }
        pending.remove(requestId)
        call.timeout?.cancel(false)
        call.callback(Result.failure(NativePhpCancelledException(requestId, "NativePHP request was cancelled.")))
        return true
    }

    override fun close() {
        if (!closed.compareAndSet(false, true)) {
            return
        }
        pending.keys.toList().forEach { requestId ->
            val call = pending[requestId] ?: return@forEach
            if (call.completed.compareAndSet(false, true)) {
                call.future?.cancel(true)
                runCatching { invoker.cancel(requestId) }
                pending.remove(requestId)
                call.timeout?.cancel(false)
                call.callback(
                    Result.failure(
                        NativePhpInterruptedException(requestId, "NativePHP transport was closed."),
                    ),
                )
            }
        }
        scheduler.shutdownNow()
        worker.shutdownNow()
    }

    private fun buildRequest(requestId: String, payload: String): String {
        val payloadElement = json.parseToJsonElement(payload)
        if (payloadElement !is JsonObject) {
            throw IllegalArgumentException("The PHP payload must be a JSON object.")
        }
        return buildJsonObject {
            put("request_id", requestId)
            put("payload", payloadElement)
        }.toString()
    }

    private fun decodeResponse(requestId: String, rawResponse: String): NativePhpResponseEnvelope {
        val response = try {
            json.decodeFromString<NativePhpResponseEnvelope>(rawResponse)
        } catch (error: Exception) {
            throw NativePhpInvalidResponseException("NativePHP returned invalid JSON.", error)
        }
        if (response.requestId != requestId) {
            throw NativePhpInvalidResponseException("NativePHP response correlation id does not match the request.")
        }
        when (response.status) {
            "ok" -> if (response.error != null) {
                throw NativePhpInvalidResponseException("A successful NativePHP response cannot contain an error.")
            }
            "error" -> {
                val error = response.error
                    ?: throw NativePhpInvalidResponseException("An error response must include an error envelope.")
                if (error.code == "CAPABILITY_UNKNOWN") {
                    throw NativePhpCapabilityException(error.message)
                }
                throw NativePhpErrorException(error.code, error.message)
            }
            else -> throw NativePhpInvalidResponseException("Unknown NativePHP response status '${response.status}'.")
        }
        return response
    }

    private fun failTimeout(call: PendingCall, timeoutMillis: Long) {
        if (!call.completed.compareAndSet(false, true)) {
            return
        }
        call.future?.cancel(true)
        runCatching { invoker.cancel(call.requestId) }
        pending.remove(call.requestId)
        call.callback(
            Result.failure(
                NativePhpTimeoutException(
                    call.requestId,
                    "NativePHP request timed out after ${timeoutMillis}ms.",
                ),
            ),
        )
    }

    private fun complete(call: PendingCall, result: Result<NativePhpResponseEnvelope>) {
        if (!call.completed.compareAndSet(false, true)) {
            return
        }
        pending.remove(call.requestId)
        call.timeout?.cancel(false)
        call.callback(result)
    }

    private class PendingCall(
        val requestId: String,
        val callback: (Result<NativePhpResponseEnvelope>) -> Unit,
        val completed: AtomicBoolean = AtomicBoolean(false),
        var future: Future<*>? = null,
        var timeout: Future<*>? = null,
    )

    private companion object {
        const val DEFAULT_LIBRARY_NAME = "nativephp"
    }
}

private class JniNativePhpInvoker : NativePhpInvoker {
    override fun call(request: String): String = NativePhpJni.nativephpCall(request)

    override fun cancel(requestId: String): Boolean = NativePhpJni.nativephpCancel(requestId)
}

private object NativePhpJni {
    @JvmStatic
    external fun nativephpCall(request: String): String

    @JvmStatic
    external fun nativephpCancel(requestId: String): Boolean
}
