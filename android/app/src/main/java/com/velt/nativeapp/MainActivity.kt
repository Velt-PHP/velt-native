package com.velt.nativeapp

import android.os.Bundle
import android.util.Log
import androidx.activity.ComponentActivity
import androidx.activity.compose.setContent
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.material3.Surface
import androidx.compose.ui.Modifier
import org.json.JSONObject

class MainActivity : ComponentActivity() {
    private val transport: NativePhpTransport by lazy { JniNativePhpTransport() }

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        val rawDocument = intent.getStringExtra("velt.ui.document") ?: error("Missing UI document")
        val document = UiProtocol.decode(rawDocument)
        setContent {
            VeltTheme(document.themeMode) {
                Surface(modifier = Modifier.fillMaxSize()) {
                    VeltDocument(document) { nodeId, event ->
                        val payload = JSONObject().put("node_id", nodeId).put("event", event).toString()
                        transport.call(payload) { result ->
                            result.exceptionOrNull()?.let { Log.e("VeltNative", "PHP callback failed", it) }
                        }
                    }
                }
            }
        }
    }

    override fun onDestroy() {
        transport.close()
        super.onDestroy()
    }
}
