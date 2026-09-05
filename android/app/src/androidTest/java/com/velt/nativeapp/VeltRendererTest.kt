package com.velt.nativeapp

import androidx.compose.ui.test.assertIsDisplayed
import androidx.compose.ui.test.junit4.createComposeRule
import androidx.compose.ui.test.onNodeWithText
import org.junit.Rule
import org.junit.Test

class VeltRendererTest {
    @get:Rule
    val composeRule = createComposeRule()

    @Test
    fun rendersDocumentWithoutWebViewAndRoutesPressEvent() {
        val document = UiProtocol.decode(
            """{"protocol_version":1,"root":{"type":"Column","id":"home","key":"home","accessibility":{"label":"Home"},"children":[{"type":"Text","id":"title","key":"title","props":{"text":"Velt"}},{"type":"Button","id":"action","key":"action","props":{"text":"Continue"}}]}}"""
        )
        var event = ""

        composeRule.setContent { VeltDocument(document) { id, action -> event = \"$id:$action\" } }
        composeRule.onNodeWithText("Velt").assertIsDisplayed()
        composeRule.onNodeWithText("Continue").performClick()
        assert(event == "action:press")
    }
}
