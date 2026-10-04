package com.velt.nativeapp

import kotlinx.serialization.json.Json
import kotlinx.serialization.json.JsonObject
import kotlinx.serialization.json.JsonPrimitive
import kotlinx.serialization.json.booleanOrNull
import kotlinx.serialization.json.contentOrNull
import kotlinx.serialization.json.intOrNull
import kotlinx.serialization.json.jsonArray
import kotlinx.serialization.json.jsonObject
import kotlinx.serialization.json.jsonPrimitive

data class UiNode(
    val type: String,
    val id: String,
    val key: String,
    val props: JsonObject,
    val accessibility: JsonObject,
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
        val document = Json.parseToJsonElement(json).jsonObject
        val version = document.int("protocol_version", -1)
        check(version == CURRENT_VERSION) { "Unsupported UI protocol version: $version" }
        val themeMode = document["theme"]?.jsonObject?.string("mode") ?: "light"
        check(themeMode == "light" || themeMode == "dark") { "Unsupported UI theme mode: $themeMode" }
        return UiDocument(version, decodeNode(document.getValue("root").jsonObject), themeMode)
    }

    private fun decodeNode(json: JsonObject): UiNode {
        val type = json.string("type")
        val id = json.string("id")
        val key = json.string("key")
        check(type in supportedTypes) { "Unsupported UI node type: $type" }
        check(id.isNotEmpty() && key.isNotEmpty()) { "UI nodes require stable id and key" }

        val children = json["children"]?.jsonArray?.map { decodeNode(it.jsonObject) } ?: emptyList()
        return UiNode(
            type,
            id,
            key,
            json["props"]?.jsonObject ?: JsonObject(emptyMap()),
            json["accessibility"]?.jsonObject ?: JsonObject(emptyMap()),
            children
        )
    }
}

private fun JsonObject.string(name: String): String = this[name]?.jsonPrimitive?.contentOrNull ?: ""

private fun JsonObject.int(name: String, fallback: Int): Int = this[name]?.jsonPrimitive?.intOrNull ?: fallback

fun JsonObject.stringValue(name: String): String = string(name)

fun JsonObject.intValue(name: String): Int = int(name, 0)

fun JsonObject.booleanValue(name: String): Boolean = this[name]?.jsonPrimitive?.booleanOrNull ?: false
