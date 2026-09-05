<?php

declare(strict_types=1);

namespace Velt\Native\Ui;

use Velt\Native\Exceptions\NativeCapabilityException;

final class UiTheme
{
    /** @param array<string, int|float|string|bool> $tokens */
    public function __construct(private readonly string $mode, private readonly array $tokens)
    {
        if (!in_array($mode, ['light', 'dark'], true) || $tokens === []) {
            throw new NativeCapabilityException('A UI theme requires light or dark mode and tokens.', 'invalid_theme');
        }

        foreach ($tokens as $name => $value) {
            if ($name === '' || !is_scalar($value)) {
                throw new NativeCapabilityException('UI theme tokens must have names and scalar values.', 'invalid_theme');
            }
        }
    }

    /** @param array<string, int|float|string|bool> $tokens */
    public static function light(array $tokens): self
    {
        return new self('light', $tokens);
    }

    /** @param array<string, int|float|string|bool> $tokens */
    public static function dark(array $tokens): self
    {
        return new self('dark', $tokens);
    }

    /** @return array{mode: string, tokens: array<string, int|float|string|bool>} */
    public function toArray(): array
    {
        return ['mode' => $this->mode, 'tokens' => $this->tokens];
    }
}
