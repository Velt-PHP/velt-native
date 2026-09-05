package com.velt.nativeapp

import androidx.compose.foundation.Image
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.verticalScroll
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.Info
import androidx.compose.material3.Button
import androidx.compose.material3.CircularProgressIndicator
import androidx.compose.material3.HorizontalDivider
import androidx.compose.material3.Icon
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.OutlinedTextField
import androidx.compose.material3.Switch
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.ui.Modifier
import androidx.compose.ui.res.painterResource
import androidx.compose.ui.semantics.contentDescription
import androidx.compose.ui.semantics.semantics
import androidx.compose.ui.unit.dp

@Composable
fun VeltDocument(document: UiDocument, onEvent: (String, String) -> Unit) {
    RenderNode(document.root, onEvent)
}

@Composable
private fun RenderNode(node: UiNode, onEvent: (String, String) -> Unit) {
    val modifier = Modifier.semantics {
        node.accessibility.optString("label").takeIf { it.isNotEmpty() }?.let { contentDescription = it }
    }
    when (node.type) {
        "Column" -> Column(modifier) { node.children.forEach { RenderNode(it, onEvent) } }
        "Row" -> Row(modifier) { node.children.forEach { RenderNode(it, onEvent) } }
        "Stack" -> Box(modifier) { node.children.forEach { RenderNode(it, onEvent) } }
        "ScrollView" -> Column(modifier.verticalScroll(rememberScrollState())) { node.children.forEach { RenderNode(it, onEvent) } }
        "Text" -> Text(node.props.optString("text"), modifier)
        "Image" -> Image(
            painter = painterResource(node.props.getInt("resource")),
            contentDescription = node.accessibility.optString("label").ifEmpty { null },
            modifier = modifier
        )
        "Icon" -> {
            check(node.props.optString("name") == "info") { "Unsupported icon name" }
            Icon(Icons.Default.Info, node.accessibility.optString("label").ifEmpty { null }, modifier)
        }
        "Divider" -> HorizontalDivider(modifier)
        "Button", "Pressable" -> Button(onClick = { onEvent(node.id, "press") }, modifier = modifier) {
            node.children.forEach { RenderNode(it, onEvent) }
            if (node.children.isEmpty()) Text(node.props.optString("text"))
        }
        "Input" -> {
            val value = remember(node.id) { mutableStateOf(node.props.optString("value")) }
            OutlinedTextField(value.value, { value.value = it; onEvent(node.id, "input:$it") }, modifier)
        }
        "Toggle" -> {
            val checked = remember(node.id) { mutableStateOf(node.props.optBoolean("checked")) }
            Switch(checked.value, { checked.value = it; onEvent(node.id, "toggle:$it") }, modifier)
        }
        "List" -> LazyColumn(modifier) { items(node.children, key = { it.key }) { RenderNode(it, onEvent) } }
        "ListItem" -> Row(modifier.fillMaxWidth().clickable { onEvent(node.id, "press") }.padding(16.dp)) { node.children.forEach { RenderNode(it, onEvent) } }
        "NavigationBar" -> Row(modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceEvenly) { node.children.forEach { RenderNode(it, onEvent) } }
        "LoadingIndicator" -> CircularProgressIndicator(modifier)
        else -> error("Unsupported UI node type: \${node.type}")
    }
}

@Composable
fun VeltTheme(mode: String, content: @Composable () -> Unit) {
    val colors = if (mode == "dark") {
        androidx.compose.material3.darkColorScheme()
    } else {
        androidx.compose.material3.lightColorScheme()
    }
    MaterialTheme(colorScheme = colors, content = content)
}
