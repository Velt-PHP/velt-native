package com.velt.nativeapp

import org.json.JSONArray
import org.json.JSONObject

data class UiNode(
    val type: String,
    val id: String,
    val key: String,
    val props: JSONObject,
    val accessibility: JSONObject,
    val children: List<UiNode>
)

data class UiDocument(val protocolVersion: Int, val root: UiNode, val themeMode: String)

object UiProtocol {
    const val CURRENT_VERSION = 1
    private val supportedTypes = setOf(
        "Column", "Row", "Stack", "ScrollView", "Text", "Image", "Icon", "Divider",
        "Button", "Pressable", "Input", "Toggle", "List", "ListItem", "NavigationBar",
        "LoadingIndicator"
    )

    fun negotiate(peerVersions: List<Int>): Int {
        require(peerVersions.all { it > 0 }) { "UI protocol versions must be positive" }
        check(CURRENT_VERSION in peerVersions) { "No compatible UI protocol version" }
        return CURRENT_VERSION
    }

    fun decode(json: String): UiDocument {
        val document = JSONObject(json)
        val version = document.optInt("protocol_version", -1)
        check(version == CURRENT_VERSION) { "Unsupported UI protocol version: $version" }
        val themeMode = document.optJSONObject("theme")?.optString("mode", "light") ?: "light"
        check(themeMode == "light" || themeMode == "dark") { "Unsupported UI theme mode: $themeMode" }
        return UiDocument(version, decodeNode(document.getJSONObject("root")), themeMode)
    }

    private fun decodeNode(json: JSONObject): UiNode {
        val type = json.optString("type")
        val id = json.optString("id")
        val key = json.optString("key")
        check(type in supportedTypes) { "Unsupported UI node type: $type" }
        check(id.isNotEmpty() && key.isNotEmpty()) { "UI nodes require stable id and key" }

        val values = json.optJSONArray("children") ?: JSONArray()
        val children = buildList {
            for (index in 0 until values.length()) add(decodeNode(values.getJSONObject(index)))
        }
        return UiNode(
            type,
            id,
            key,
            json.optJSONObject("props") ?: JSONObject(),
            json.optJSONObject("accessibility") ?: JSONObject(),
            children
        )
    }
}
