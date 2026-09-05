<?php

declare(strict_types=1);

namespace Velt\Native\Tests;

use PHPUnit\Framework\TestCase;
use Velt\Native\Dialog;
use Velt\Native\Exceptions\NativeCapabilityException;
use Velt\Native\SandboxFileStore;
use Velt\Native\Testing\FakeNativeBridge;

final class NativeCapabilitiesTest extends TestCase
{
    public function testDialogAndToastUseTypedBridgeMethods(): void
    {
        $bridge = (new FakeNativeBridge())
            ->respondWith('Ui.Dialog', ['success' => true])
            ->respondWith('Ui.Toast', ['success' => true]);
        $ui = new Dialog($bridge);

        self::assertSame(['success' => true], $ui->show('Title', 'Message'));
        self::assertSame(['success' => true], $ui->toast('Saved'));
        self::assertSame('Ui.Dialog', $bridge->calls()[0]['method']);
        self::assertSame('Ui.Toast', $bridge->calls()[1]['method']);
    }

    public function testSandboxFileStoreValidatesPathsAndDelegatesOperations(): void
    {
        $bridge = (new FakeNativeBridge())
            ->respondWith('Storage.WriteFile', ['success' => true])
            ->respondWith('Storage.ReadFile', ['contents' => 'data'])
            ->respondWith('Storage.DeleteFile', ['success' => true]);
        $files = new SandboxFileStore($bridge);

        $files->write('documents/note.txt', 'data');
        self::assertSame('data', $files->read('documents/note.txt'));
        $files->delete('documents/note.txt');
        self::assertSame('documents/note.txt', $bridge->calls()[0]['parameters']['path']);
    }

    public function testSandboxFileStoreRejectsTraversal(): void
    {
        $files = new SandboxFileStore(new FakeNativeBridge());

        try {
            $files->read('../outside.txt');
            self::fail('Expected invalid path exception.');
        } catch (NativeCapabilityException $exception) {
            self::assertSame('invalid_path', $exception->codeName());
        }
    }

    public function testUiPermissionErrorsRemainTyped(): void
    {
        $bridge = (new FakeNativeBridge())->respondWith('Ui.Dialog', [
            'error' => ['code' => 'permission_denied', 'message' => 'Permission denied.'],
        ]);

        $this->expectException(NativeCapabilityException::class);
        $this->expectExceptionMessage('Permission denied.');

        (new Dialog($bridge))->show('Title', 'Message');
    }
}
