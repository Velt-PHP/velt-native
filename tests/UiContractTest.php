<?php

declare(strict_types=1);

namespace Velt\Native\Tests;

use PHPUnit\Framework\TestCase;
use Velt\Native\Exceptions\NativeCapabilityException;
use Velt\Native\Ui\UiDocument;
use Velt\Native\Ui\UiNavigation;
use Velt\Native\Ui\UiNodeFactory;
use Velt\Native\Ui\UiProtocol;
use Velt\Native\Ui\UiTheme;

final class UiContractTest extends TestCase
{
    public function testProtocolNegotiatesTheCurrentVersion(): void
    {
        $protocol = new UiProtocol();

        self::assertSame(1, $protocol->negotiate([2, 1]));
        self::assertSame(1, $protocol->version());
    }

    public function testUnsupportedProtocolFailsDeterministically(): void
    {
        try {
            (new UiProtocol())->negotiate([2]);
            self::fail('Expected protocol negotiation failure.');
        } catch (NativeCapabilityException $exception) {
            self::assertSame('protocol_not_supported', $exception->codeName());
        }
    }

    public function testDocumentContainsStableNodeIdentityAccessibilityAndPortableTokens(): void
    {
        $document = new UiDocument(
            UiNodeFactory::column(
                'screen-home',
                'home',
                ['padding' => 16, 'colorToken' => 'surface'],
                [UiNodeFactory::text('title', 'title', ['text' => 'Welcome'], [], ['label' => 'Welcome'])],
                ['role' => 'main'],
            ),
        );

        $payload = $document->toArray();
        self::assertSame(1, $payload['protocol_version']);
        self::assertSame('Column', $payload['root']['type']);
        self::assertSame('screen-home', $payload['root']['id']);
        self::assertSame('main', $payload['root']['accessibility']['role']);
        self::assertSame('surface', $payload['root']['props']['colorToken']);
        self::assertSame('Text', $payload['root']['children'][0]['type']);
    }

    public function testNavigationCommandsAreExplicit(): void
    {
        self::assertSame(['action' => 'push', 'route' => 'settings'], UiNavigation::push('settings'));
        self::assertSame(['action' => 'replace', 'route' => 'home'], UiNavigation::replace('home'));
        self::assertSame(['action' => 'back', 'route' => null], UiNavigation::back());
    }

    public function testLightAndDarkThemesExposeValidatedPortableTokens(): void
    {
        self::assertSame(
            ['mode' => 'light', 'tokens' => ['surface' => '#ffffff']],
            UiTheme::light(['surface' => '#ffffff'])->toArray(),
        );
        self::assertSame('dark', UiTheme::dark(['surface' => '#000000'])->toArray()['mode']);
    }

    public function testInvalidNodeAndRouteAreRejected(): void
    {
        try {
            UiNodeFactory::node('Html', 'screen', 'screen');
            self::fail('Expected invalid node failure.');
        } catch (NativeCapabilityException $exception) {
            self::assertSame('invalid_ui_node', $exception->codeName());
        }

        try {
            UiNavigation::push('../outside');
            self::fail('Expected invalid route failure.');
        } catch (NativeCapabilityException $exception) {
            self::assertSame('invalid_route', $exception->codeName());
        }
    }
}
