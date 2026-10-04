<?php

declare(strict_types=1);

namespace Velt\Native\Ui;

final class UiDocument
{
    public function __construct(
        private readonly UiNode $root,
        private readonly UiProtocol $protocol = new UiProtocol(),
        private readonly ?UiTheme $theme = null
    )
    {
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        $document = ['protocol_version' => $this->protocol->version(), 'root' => $this->root->toArray()];
        if ($this->theme !== null) {
            $document['theme'] = $this->theme->toArray();
        }

        return $document;
    }

    public function negotiate(array $peerVersions): void
    {
        $this->protocol->negotiate($peerVersions);
    }
}
