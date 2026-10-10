# Environnement de build Android

## Versions epinglees

- JDK: 17 LTS.
- Gradle Wrapper: 8.9.
- Android Gradle Plugin: 8.7.3.
- Kotlin: 2.0.21.
- Kotlin Serialization JSON: 1.7.3.
- Compose BOM: 2024.12.01.
- AndroidX Activity Compose: 1.10.0.
- compileSdk: 35.
- targetSdk: 35.
- minSdk: 26.
- Build Tools utilisees localement: 34.0.0.
- ABI cible: x86_64 pour l'emulateur et arm64-v8a pour l'appareil.

Le projet ne versionne aucun `local.properties`, chemin SDK ou artefact de build. Le SDK est fourni par l'environnement de build via `ANDROID_HOME` ou `ANDROID_SDK_ROOT`.

## Commandes reproductibles

Depuis `velt-native/android`:

```powershell
$env:JAVA_HOME = '<chemin-vers-jdk-17>'
$env:ANDROID_HOME = "$env:LOCALAPPDATA\Android\Sdk"
$env:ANDROID_SDK_ROOT = $env:ANDROID_HOME

.\gradlew.bat --version
.\gradlew.bat :app:test --no-daemon --console plain
.\gradlew.bat :app:assembleDebug --no-daemon --console plain
.\gradlew.bat :app:connectedCheck --no-daemon --console plain
```

Sur Linux/macOS, utiliser les scripts `gradlew` et les chemins correspondants du JDK/SDK.

## Preuves locales du 2026-10-04

- `gradlew.bat --version`: Gradle 8.9 avec JVM 17.0.20.1.
- `:app:test`: passe; tests unitaires Kotlin du protocole et de la navigation.
- `:app:assembleDebug`: passe.
- APK: `app/build/outputs/apk/debug/app-debug.apk`.
- SHA-256 APK debug: `8EA0660FC71009C92EE9D6570E815FF7C6F2E1DCC89A697553A4714736A78B79`.
- Manifeste: compile SDK 35, `MainActivity` launcher; aucune permission Android explicite dans le manifeste source.
- `:app:connectedDebugAndroidTest`: passe sur appareil physique `SM-A217F` API 31, ABI `arm64-v8a`; 1 test execute.
- `:app:connectedCheck`: valide sur appareil physique arm64; le passage emulateur x86_64 reste a produire pour fermer la matrice complete.

## Installation et test connecte

Un emulateur x86_64 API 35 ou un appareil arm64 doit etre demarre avant:

```powershell
$sdk = "$env:LOCALAPPDATA\Android\Sdk"
& "$sdk\platform-tools\adb.exe" devices -l
& "$sdk\platform-tools\adb.exe" install -r app/build/outputs/apk/debug/app-debug.apk
.\gradlew.bat :app:connectedCheck --no-daemon --console plain
```

Le resultat `connectedCheck` ne doit etre declare vert qu'avec au moins un appareil `device` liste par ADB. La matrice finale doit inclure l'emulateur x86_64 et un appareil ou device farm arm64.

## Nettoyage et securite

- Les fichiers `android/**/build`, `.gradle` et `local.properties` sont ignores.
- Le wrapper, son JAR et ses scripts sont versionnes.
- Aucun repository local, `dev-main` ou chemin absolu n'est utilise par Gradle.
- Les logs et hashes de release doivent etre publies comme artefacts CI, pas dans les sources.
