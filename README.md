# Velt Native

`velt/native` is the Android boundary for Velt applications. It defines PHP-facing native bridge contracts and will host the embedded PHP lifecycle, JNI transport, Compose renderer integration and Android packaging support derived from audited NativePHP Mobile architecture.

> Status: experimental alpha. The current package provides bridge contracts, device façades and test doubles. It does not yet prove the real JNI bridge, render a complete Compose application or independently produce an APK.

## Requirements

- PHP 8.4 for Android/cross-platform projects;
- Android shell and ABI libraries for real device calls;
- Java, Android SDK/NDK, Gradle and ADB for implementation/build work;
- no Laravel or Illuminate runtime dependency.

Because the package is not yet indexed on Packagist, experimental skeletons temporarily declare its public GitHub VCS source. This is a release limitation, not a local path dependency.

## Current API

```php
<?php

use Velt\Native\Bridge\NativePhpBridge;
use Velt\Native\Device;

$device = new Device(new NativePhpBridge());

$information = $device->info();
$device->vibrate();
```

`NativePhpBridge` detects the actual `nativephp_call()` function. Outside a compatible Android runtime it fails explicitly with `NativeBridgeException`; it never pretends a native call succeeded.

## Testing with the fake bridge

```php
<?php

use Velt\Native\Device;
use Velt\Native\Testing\FakeNativeBridge;

$bridge = (new FakeNativeBridge())
    ->respondWith('Device.Info', [
        'platform' => 'android',
        'model' => 'test-device',
    ])
    ->respondWith('Device.Vibrate', ['success' => true]);

$device = new Device($bridge);
```

The fake is exclusively a PHP unit-test double. It does not validate C extension loading, JNI, Kotlin, Android threads, permissions, lifecycle, ABI packaging or callbacks. Release variants must exclude it, and instrumented tests must assert the registered bridge is JNI-backed.

## Target installed-application flow

```text
Compose gesture
  → stable Velt event id and typed payload
  → Kotlin renderer/runtime
  → JNI and nativephp_call() C extension
  → embedded PHP 8.4 thread
  → Velt kernel/service handler
  → typed success/error/event
  → Kotlin state update
  → Compose recomposition
```

The final runtime operates in the Android application process, without starting a local web server and without rendering native screens in a WebView.

## Planned capability model

Native calls use versioned capability identifiers such as device information, vibration, dialogs, filesystem selection, application storage, clipboard, connectivity and lifecycle events. Each capability must define:

- method name and typed arguments;
- synchronous/asynchronous behavior;
- Android permission requirements;
- stable result/error envelope;
- timeout and cancellation behavior;
- minimum Android API and availability check;
- test fixture plus real instrumented test.

Unsupported capabilities return an explicit `capability_not_available` error rather than falling back to a fake.

## Runtime lifecycle

The Android runtime will initialize PHP on a dedicated thread, load Composer autoloading and `bootstrap/app.php`, boot mobile-safe providers, keep controlled process state between interactions and implement pause, resume, reset and shutdown. Activity objects must not leak into PHP singletons.

SQLite migrations run before application readiness. Fatal PHP errors are converted into structured boot diagnostics and trigger a controlled runtime recovery policy instead of blocking Android's main thread.

## Compose renderer boundary

Velt UI supplies a versioned, renderer-neutral tree. Android maps supported nodes to Material 3/Compose primitives with stable ids, accessibility, navigation, themes, forms and events. Arbitrary web CSS is not interpreted as native UI. Portable tokens are mapped explicitly, while unsupported properties produce diagnostics.

## APK and AAB packaging

The final build pipeline must generate:

- debug APK for installation/testing;
- signed release APK for direct distribution where appropriate;
- signed AAB for Google Play;
- arm64-v8a device and x86_64 emulator libraries;
- Composer production autoload and project PHP sources;
- manifest/permissions derived from used capabilities;
- hashes, SBOM, dependency/source revisions and provenance.

A generic Preview companion cannot export arbitrary native plugins without rebuilding. The final application is project-specific.

## NativePHP provenance

The initial implementation audit used NativePHP Mobile v4 sources, including `NativePHP/mobile-air` commit `4c936299bcb3bbe80d131f28c3924db6eeff6f67`, under MIT. Velt pins upstream revisions, verifies downloaded hashes and preserves copyright/license notices for redistributed work.

Updates to upstream runtime code require ABI, bridge, lifecycle, renderer and application smoke tests before changing the pin.

## Required release evidence

- PHP → C `nativephp_call()` → JNI/Kotlin → PHP round-trip on x86_64 emulator;
- the same smoke test on arm64 physical hardware or device farm;
- async callback, timeout, cancellation and error propagation tests;
- Compose screen rendering and user interaction without WebView;
- pause/resume/rotation/process recreation and memory tests;
- SQLite migration/persistence after restart;
- reproducible APK/AAB and installation smoke test;
- proof that fake/testing namespaces are absent from release artifacts.

## Testing the current package

```bash
composer install
composer validate --strict
composer test
```

These tests validate PHP contracts only. They are necessary but insufficient for Android promotion.

## Repository relationships

- `velt-mobile-architecture` owns system diagrams, protocol and release-gate documentation.
- `velt-ui` owns portable node and token contracts.
- `veltphp-kernel` owns runtime-independent application lifecycle contracts.
- `velt-mobile-preview` owns the development companion product.
- `veltphp-cli` will own user-facing Android install/run/package commands.
- `velt-integration-quality` owns the cross-repository Android release gate.

## Security

Native arguments are untrusted inputs and require strict schema/size validation. Release builds deny cleartext traffic, debugging and sensitive logs. Filesystem APIs are sandboxed or mediated by Android system pickers. Keystores and signing passwords stay outside Git.

## Contributing and license

Bridge changes require both PHP contract tests and an Android instrumented-test plan. Never close an Android issue with only a fake-based test. The package is MIT licensed and preserves applicable upstream notices.
