<?php

declare(strict_types=1);

namespace Velt\Native\Ui;

final class UiNodeFactory
{
    public const TYPES = [
        'Column', 'Row', 'Stack', 'ScrollView', 'Text', 'Image', 'Icon', 'Divider',
        'Button', 'Pressable', 'Input', 'Toggle', 'List', 'ListItem', 'NavigationBar', 'LoadingIndicator',
    ];

    /** @param array<string, mixed> $props @param list<UiNode> $children @param array<string, string> $accessibility */
    public static function node(string $type, string $id, string $key, array $props = [], array $children = [], array $accessibility = []): UiNode
    {
        return new UiNode($type, $id, $key, $props, $children, $accessibility);
    }

    public static function __callStatic(string $name, array $arguments): UiNode
    {
        $type = match ($name) {
            'column' => 'Column', 'row' => 'Row', 'stack' => 'Stack', 'scrollView' => 'ScrollView',
            'text' => 'Text', 'image' => 'Image', 'icon' => 'Icon', 'divider' => 'Divider',
            'button' => 'Button', 'pressable' => 'Pressable', 'input' => 'Input', 'toggle' => 'Toggle',
            'list' => 'List', 'listItem' => 'ListItem', 'navigationBar' => 'NavigationBar',
            'loadingIndicator' => 'LoadingIndicator', default => null,
        };

        if ($type === null) {
            throw new \BadMethodCallException(sprintf('Unknown UI node factory method "%s".', $name));
        }

        return self::node($type, ...$arguments);
    }
}
