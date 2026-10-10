package com.velt.nativeapp

import java.util.concurrent.CountDownLatch
import java.util.concurrent.TimeUnit
import java.util.concurrent.atomic.AtomicInteger
import kotlinx.serialization.json.Json
import kotlinx.serialization.json.jsonObject
import kotlinx.serialization.json.jsonPrimitive
import org.junit.Assert.assertEquals
import org.junit.Assert.assertTrue
import org.junit.Test
import org.junit.Assert.assertThrows

class NativePhpTransportTest {
    @Test
    fun successful_call_preserves_request_correlation() {
        val seenRequest = arrayOfNulls<String>(1)
        val invoker = TestInvoker { request ->
            seenRequest[0] = request
            val requestId = Json.parseToJsonElement(request).jsonObject["request_id"]!!.jsonPrimitive.content
            """{"request_id":"$requestId","status":"ok","result":{"accepted":true}}"""
        }
        val transport = transport(invoker)
        try {
            val result = await { callback -> transport.call("""{"capability":"Device.GetInfo"}""", callback = callback) }

            assertTrue(result.isSuccess)
            assertEquals(result.getOrThrow().requestId, Json.parseToJsonElement(seenRequest[0]!!).jsonObject["request_id"]!!.jsonPrimitive.content)
            assertEquals("ok", result.getOrThrow().status)
        } finally {
            transport.close()
        }
    }

    @Test
    fun invalid_response_fails_explicitly() {
        val transport = transport(TestInvoker { "not-json" })
        try {
            val result = await { callback -> transport.call("{}", callback = callback) }

            assertTrue(result.isFailure)
            assertTrue(result.exceptionOrNull() is NativePhpInvalidResponseException)
        } finally {
            transport.close()
        }
    }

    @Test
    fun php_error_and_unknown_capability_are_typed() {
        val transport = transport(
            TestInvoker {
                val requestId = Json.parseToJsonElement(it).jsonObject["request_id"]!!.jsonPrimitive.content
                """{"request_id":"$requestId","status":"error","error":{"code":"CAPABILITY_UNKNOWN","message":"Unknown capability"}}"""
            },
        )
        try {
            val result = await { callback -> transport.call("{}", callback = callback) }

            assertTrue(result.isFailure)
            assertTrue(result.exceptionOrNull() is NativePhpCapabilityException)
        } finally {
            transport.close()
        }
    }

    @Test
    fun php_error_is_typed() {
        val transport = transport(
            TestInvoker {
                val requestId = Json.parseToJsonElement(it).jsonObject["request_id"]!!.jsonPrimitive.content
                """{"request_id":"$requestId","status":"error","error":{"code":"PHP_ERROR","message":"PHP failed"}}"""
            },
        )
        try {
            val result = await { callback -> transport.call("{}", callback = callback) }

            assertTrue(result.isFailure)
            assertTrue(result.exceptionOrNull() is NativePhpErrorException)
            assertEquals("PHP_ERROR", (result.exceptionOrNull() as NativePhpErrorException).code)
        } finally {
            transport.close()
        }
    }

    @Test
    fun timeout_cancels_the_native_request_and_completes_once() {
        val cancelCount = AtomicInteger()
        val transport = transport(
            TestInvoker(cancelBlock = { cancelCount.incrementAndGet(); true }) {
                Thread.sleep(5_000)
                "{}"
            },
        )
        try {
            val result = await(timeoutMillis = 2_000) { callback ->
                transport.call("{}", timeoutMillis = 25, callback = callback)
            }

            assertTrue(result.isFailure)
            assertTrue(result.exceptionOrNull() is NativePhpTimeoutException)
            assertEquals(1, cancelCount.get())
        } finally {
            transport.close()
        }
    }

    @Test
    fun cancellation_completes_with_cancelled_error() {
        val requestId = arrayOfNulls<String>(1)
        val callbackLatch = CountDownLatch(1)
        val transport = transport(TestInvoker { Thread.sleep(5_000); "{}" })
        try {
            requestId[0] = transport.call("{}") {
                assertTrue(it.isFailure)
                assertTrue(it.exceptionOrNull() is NativePhpCancelledException)
                callbackLatch.countDown()
            }

            assertTrue(transport.cancel(requestId[0]!!))
            assertTrue(callbackLatch.await(2, TimeUnit.SECONDS))
        } finally {
            transport.close()
        }
    }

    @Test
    fun missing_library_fails_with_typed_error() {
        val error = assertThrows(NativePhpLibraryUnavailableException::class.java) {
            JniNativePhpTransport(
                libraryLoader = NativePhpLibraryLoader { throw UnsatisfiedLinkError("missing") },
                invoker = TestInvoker { "{}" },
            )
        }

        assertTrue(error.message!!.contains("could not be loaded"))
    }

    private fun transport(invoker: NativePhpInvoker): JniNativePhpTransport = JniNativePhpTransport(
        libraryLoader = NativePhpLibraryLoader { },
        invoker = invoker,
    )

    private fun await(
        timeoutMillis: Long = 2_000,
        submit: ((Result<NativePhpResponseEnvelope>) -> Unit) -> String,
    ): Result<NativePhpResponseEnvelope> {
        var result: Result<NativePhpResponseEnvelope>? = null
        val latch = CountDownLatch(1)
        submit {
            result = it
            latch.countDown()
        }
        assertTrue(latch.await(timeoutMillis, TimeUnit.MILLISECONDS))
        return result ?: error("Callback did not provide a result.")
    }

    private class TestInvoker(
        private val cancelBlock: (String) -> Boolean = { true },
        private val callBlock: (String) -> String,
    ) : NativePhpInvoker {
        override fun call(request: String): String = callBlock(request)

        override fun cancel(requestId: String): Boolean = cancelBlock(requestId)
    }
}
