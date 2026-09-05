<?php

declare(strict_types=1);

namespace Velt\Native\Ui;

use Velt\Native\Exceptions\NativeCapabilityException;

final class UiNode
{
    /** @param array<string, mixed> $props @param list<UiNode> $children @param array<string, string> $accessibility */
    public function __construct(
        private readonly string $type,
        private readonly string $id,
        private readonly string $key,
        private readonly array $props = [],
        private readonly array $children = [],
        private readonly array $accessibility = []
    ) {
        if (!in_array($type, UiNodeFactory::TYPES, true) || $id === '' || $key === '') {
            throw new NativeCapabilityException('A UI node requires a supported type, id and key.', 'invalid_ui_node');
        }

        foreach ($children as $child) {
            if (!$child instanceof self) {
                throw new NativeCapabilityException('UI node children must be UiNode instances.', 'invalid_ui_node');
            }
        }

        foreach ($accessibility as $name => $value) {
            if (!is_string($name) || !is_string($value) || $value === '') {
                throw new NativeCapabilityException('UI accessibility values must be non-empty strings.', 'invalid_accessibility');
            }
        }
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'type' => $this->type,
            'id' => $this->id,
            'key' => $this->key,
            'props' => $this->props,
            'accessibility' => $this->accessibility,
            'children' => array_map(static fn (self $child): array => $child->toArray(), $this->children),
        ];
    }
}
