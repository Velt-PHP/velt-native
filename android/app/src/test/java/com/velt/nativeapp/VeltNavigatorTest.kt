package com.velt.nativeapp

import org.junit.Assert.assertEquals
import org.junit.Assert.assertFalse
import org.junit.Assert.assertTrue
import org.junit.Test

class VeltNavigatorTest {
    @Test
    fun supportsPushReplaceAndBack() {
        val navigator = VeltNavigator("home")
        navigator.push("settings")
        assertEquals("settings", navigator.current)
        navigator.replace("profile")
        assertEquals("profile", navigator.current)
        assertTrue(navigator.back())
        assertEquals("home", navigator.current)
        assertFalse(navigator.back())
    }
}
