# Velt Native

`velt/native` is the Android runtime boundary for Velt. It provides framework-independent PHP contracts around the NativePHP bridge and will host the Velt lifecycle, native renderer, events, and Android tooling.

> This package is an early development foundation. It does not yet produce an APK or render a complete Velt screen.

## Requirements

- PHP 8.4 or newer for Android projects;
- the Velt Android shell for on-device calls;
- no Laravel dependency.

## Bridge usage

```php
use Velt\Native\Bridge\NativePhpBridge;
use Velt\Native\Device;

$device = new Device(new NativePhpBridge());
$device->vibrate();
$info = $device->info();
```

Outside Android, `NativePhpBridge` fails explicitly. Tests should use `FakeNativeBridge`:

```php
use Velt\Native\Testing\FakeNativeBridge;

$bridge = (new FakeNativeBridge())
    ->respondWith('Device.Vibrate', ['success' => true]);
```

## NativePHP provenance

The Android implementation targets the open-source NativePHP Mobile v4 bridge contract. The initial audit was performed against `NativePHP/mobile-air` commit `4c936299bcb3bbe80d131f28c3924db6eeff6f67` (MIT). Upstream code redistributed later must retain its copyright and license notices.

## Roadmap

- boot the Velt kernel in the embedded PHP runtime;
- map Velt UI nodes to the NativePHP shared-memory wire format;
- render screens with Jetpack Compose rather than a WebView;
- add lifecycle, navigation, events, plugins, SQLite migrations and CLI commands;
- validate APK/AAB builds on CI and real Android hardware.

## Testing

```bash
composer install
composer test
```

## License

MIT
