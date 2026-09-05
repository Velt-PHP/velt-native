<?php

declare(strict_types=1);

namespace Velt\Native\Ui;

use Velt\Native\Exceptions\NativeCapabilityException;

final class UiNavigation
{
    /** @return array{action: string, route: ?string} */
    public static function push(string $route): array
    {
        return self::route('push', $route);
    }

    /** @return array{action: string, route: ?string} */
    public static function replace(string $route): array
    {
        return self::route('replace', $route);
    }

    /** @return array{action: string, route: ?string} */
    public static function back(): array
    {
        return ['action' => 'back', 'route' => null];
    }

    /** @return array{action: string, route: ?string} */
    private static function route(string $action, string $route): array
    {
        if ($route === '' || !preg_match('/^[A-Za-z][A-Za-z0-9._\/-]*$/', $route)) {
            throw new NativeCapabilityException('Navigation routes must be non-empty stable identifiers.', 'invalid_route');
        }

        return ['action' => $action, 'route' => $route];
    }
}
