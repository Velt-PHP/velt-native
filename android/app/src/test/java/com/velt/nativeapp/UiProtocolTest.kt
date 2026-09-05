package com.velt.nativeapp

import org.junit.Assert.assertEquals
import org.junit.Assert.assertThrows
import org.junit.Test

class UiProtocolTest {
    @Test
    fun negotiatesCurrentVersion() {
        assertEquals(1, UiProtocol.negotiate(listOf(2, 1)))
    }

    @Test
    fun rejectsUnknownNodeAndMissingStableIdentity() {
        assertThrows(IllegalStateException::class.java) {
            UiProtocol.decode("""{"protocol_version":1,"root":{"type":"Html","id":"x","key":"x"}}""")
        }
        assertThrows(IllegalStateException::class.java) {
            UiProtocol.decode("""{"protocol_version":1,"root":{"type":"Text","id":"","key":"x"}}""")
        }
    }

    @Test
    fun decodesThemeAndNestedNodes() {
        val document = UiProtocol.decode(
            """{"protocol_version":1,"theme":{"mode":"dark"},"root":{"type":"Column","id":"home","key":"home","children":[{"type":"Text","id":"title","key":"title","props":{"text":"Hello"}}]}}"""
        )

        assertEquals("dark", document.themeMode)
        assertEquals("Text", document.root.children.single().type)
    }
}
