package com.velt.nativeapp

class VeltNavigator(initialRoute: String) {
    private val stack = ArrayDeque<String>().apply { addLast(validate(initialRoute)) }

    val current: String
        get() = stack.last()

    fun push(route: String) {
        stack.addLast(validate(route))
    }

    fun replace(route: String) {
        stack.removeLast()
        stack.addLast(validate(route))
    }

    fun back(): Boolean {
        if (stack.size == 1) return false
        stack.removeLast()
        return true
    }

    private fun validate(route: String): String {
        require(route.matches(Regex("[A-Za-z][A-Za-z0-9._/-]*"))) { "Invalid navigation route" }
        return route
    }
}
