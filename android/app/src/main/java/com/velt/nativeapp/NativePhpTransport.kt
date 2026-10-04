package com.velt.nativeapp

import java.util.concurrent.ExecutorService
import java.util.concurrent.Executors

interface NativePhpTransport : AutoCloseable {
    fun call(payload: String, callback: (Result<String>) -> Unit)
}

/** JNI boundary. The PHP host owns the actual nativephp_call implementation. */
class JniNativePhpTransport : NativePhpTransport {
    private val executor: ExecutorService = Executors.newSingleThreadExecutor()

    override fun call(payload: String, callback: (Result<String>) -> Unit) {
        executor.execute { callback(runCatching { nativephpCall(payload) }) }
    }

    private external fun nativephpCall(payload: String): String

    override fun close() {
        executor.shutdownNow()
    }
}
