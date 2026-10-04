# Validation Android sur telephone reel

Ce guide permet de valider completement le shell Android de `velt/native` sur un telephone Android reel, notamment l'installation de l'APK et les tests instrumentes arm64.

## 1. Prerequis poste de developpement

- Windows 10/11 64 bits.
- JDK 17 LTS.
- Android SDK avec:
  - Platform API 35;
  - Build Tools 34.0.0;
  - Platform Tools incluant `adb`.
- Git.
- Connexion Internet pour telecharger les dependances Gradle lors du premier build.
- Cable USB de donnees, pas uniquement un cable de charge.
- Telephone Android arm64 compatible avec API minimale 26.

Les versions du projet sont documentees dans [`BUILD-ENVIRONMENT.md`](BUILD-ENVIRONMENT.md). Ne pas utiliser JDK 26 pour cette configuration Gradle 8.9/AGP 8.7.3.

## 2. Installer et verifier les outils

Depuis PowerShell:

```powershell
$env:JAVA_HOME = '<chemin-vers-jdk-17>'
$env:ANDROID_HOME = "$env:LOCALAPPDATA\Android\Sdk"
$env:ANDROID_SDK_ROOT = $env:ANDROID_HOME
$env:Path = "$env:JAVA_HOME\bin;$env:ANDROID_HOME\platform-tools;$env:ANDROID_HOME\emulator;$env:Path"

java -version
adb version
```

Les commandes doivent afficher Java 17 et une version ADB valide. Pour une configuration permanente, ajouter `JAVA_HOME`, `ANDROID_HOME`, `ANDROID_SDK_ROOT` et les dossiers `bin` au profil utilisateur Windows, puis ouvrir un nouveau terminal.

## 3. Activer le telephone pour ADB

Sur le telephone:

1. Ouvrir **Parametres > A propos du telephone**.
2. Taper sept fois sur **Numero de build** jusqu'a l'activation du mode developpeur.
3. Ouvrir **Options pour les developpeurs**.
4. Activer **Debogage USB**.
5. Si le telephone le demande, activer l'autorisation d'installation via USB uniquement pour un appareil de test dedie.
6. Laisser le telephone deverrouille pendant la premiere connexion.

Sur Windows, installer le pilote USB OEM du fabricant si le telephone n'apparait pas dans ADB. Ne pas contourner la verification de signature des pilotes.

## 4. Connecter et autoriser le telephone

Brancher le telephone, choisir le mode USB **Transfert de fichiers** si necessaire, puis executer:

```powershell
adb kill-server
adb start-server
adb devices -l
```

Resultats possibles:

| Etat ADB | Action |
| --- | --- |
| `device` | Le telephone est autorise et pret. |
| `unauthorized` | Deverrouiller le telephone et accepter l'empreinte RSA affichee. Relancer `adb devices -l`. |
| `offline` | Debrancher/rebrancher, revoker les autorisations USB dans les options developpeur, puis relancer le serveur ADB. |
| Aucun appareil | Verifier cable, port USB, pilote OEM, mode USB et `adb start-server`. |
| Plusieurs appareils | Utiliser `-s <serial>` pour cibler explicitement le telephone. |

La validation arm64 ne commence que lorsque le telephone apparait avec l'etat `device`.

## 5. Verifier l'architecture et la version Android

```powershell
adb shell getprop ro.product.cpu.abilist
adb shell getprop ro.build.version.sdk
adb shell getprop ro.product.model
```

Le rapport doit montrer:

- une ABI `arm64-v8a`;
- une API Android compatible avec `minSdk 26`;
- le modele et le numero de serie de l'appareil.

Conserver cette sortie dans les preuves de validation.

## 6. Construire depuis un clone propre

Depuis le dossier `velt-native/android`:

```powershell
$env:JAVA_HOME = '<chemin-vers-jdk-17>'
$env:ANDROID_HOME = "$env:LOCALAPPDATA\Android\Sdk"
$env:ANDROID_SDK_ROOT = $env:ANDROID_HOME
$env:Path = "$env:JAVA_HOME\bin;$env:ANDROID_HOME\platform-tools;$env:ANDROID_HOME\emulator;$env:Path"

git clean -ndX
.\gradlew.bat --version
.\gradlew.bat :app:test --no-daemon --console plain
.\gradlew.bat :app:assembleDebug --no-daemon --console plain
```

Ne pas executer `git clean -fdX` sans verifier la liste affichee. Aucun `local.properties`, chemin SDK ou fichier de build ne doit etre ajoute au depot.

## 7. Installer l'APK sur le telephone

```powershell
$apk = Resolve-Path .\app\build\outputs\apk\debug\app-debug.apk
adb -s <serial> install -r $apk
```

Verifier l'installation:

```powershell
adb -s <serial> shell pm path com.velt.nativeapp
adb -s <serial> shell monkey -p com.velt.nativeapp 1
```

Le lancement doit ouvrir `MainActivity`. Un crash ou une erreur de document UI doit etre capture avec `adb logcat` et corrige avant validation.

## 8. Executer les tests instrumentes

Avec le telephone connecte et autorise:

```powershell
adb -s <serial> logcat -c
.\gradlew.bat :app:connectedDebugAndroidTest --no-daemon --console plain
```

Ou pour la verification complete:

```powershell
.\gradlew.bat :app:connectedCheck --no-daemon --console plain
```

Les tests doivent compiler et s'executer sur l'appareil. Un build vert sans appareil connecte ne constitue pas une preuve instrumentee.

## 9. Collecter les preuves

```powershell
$apk = '.\app\build\outputs\apk\debug\app-debug.apk'
Get-FileHash $apk -Algorithm SHA256
adb -s <serial> shell getprop ro.product.cpu.abilist
adb -s <serial> shell getprop ro.build.version.sdk
adb -s <serial> shell getprop ro.product.model
adb -s <serial> shell dumpsys package com.velt.nativeapp
adb -s <serial> logcat -d -v threadtime > .\validation-logcat-arm64.txt
```

Le rapport de validation doit contenir:

- versions Java, Gradle, AGP, Kotlin, Compose, SDK et ADB;
- serial, modele, API et ABI du telephone;
- commande exacte de build;
- resultat de `:app:test`;
- resultat de `:app:connectedDebugAndroidTest` ou `:app:connectedCheck`;
- resultat de l'installation APK;
- hash SHA-256 de l'APK;
- logcat en cas d'erreur ou de diagnostic;
- absence de WebView pour l'ecran principal.

Les logs locaux contenant des donnees sensibles ne doivent pas etre commites sans nettoyage. Publier les preuves comme artefacts CI ou PR.

## 10. Verification fonctionnelle minimale

Verifier manuellement sur le telephone:

- le lancement de l'application;
- le rendu d'un document UI PHP;
- le rendu clair et sombre;
- les composants `Text`, `Button`, `Input`, `Toggle`, `List` et `ListItem`;
- l'accessibilite et les descriptions de contenu;
- les callbacks vers le runtime PHP;
- `push`, `replace` et `back`;
- la reprise apres rotation;
- le passage background/foreground;
- l'absence d'exception JNI ou de blocage du main thread.

## 11. Depannage

### `SDK location not found`

Verifier `ANDROID_HOME`, `ANDROID_SDK_ROOT` et la presence de `platforms\android-35`. Ne pas commiter `local.properties`.

### `unauthorized` dans ADB

Deverrouiller le telephone, accepter la cle RSA, puis:

```powershell
adb kill-server
adb start-server
adb devices -l
```

### `No connected devices`

Le telephone n'est pas visible dans l'etat `device`. Corriger le cable, le pilote, le mode USB et l'autorisation RSA avant de relancer Gradle.

### `INSTALL_FAILED_VERSION_DOWNGRADE`

Desinstaller la version existante de l'application de test ou augmenter `versionCode`. Ne pas desactiver la verification de signature.

### Crash JNI ou `UnsatisfiedLinkError`

Verifier que la bibliotheque native arm64 est presente et que le runtime fournit le symbole `nativephpCall`. Aucun fake ne doit etre utilise pour declarer la validation Android reussie.

### Tests instrumentes non executes

Verifier `adb devices -l`, puis relancer `:app:connectedDebugAndroidTest`. Le resultat `UP-TO-DATE` de la compilation ne remplace pas l'execution sur appareil.

## Condition de validation complete

L'issue est validee uniquement lorsque:

- le clone propre se construit avec le wrapper;
- `:app:test` passe;
- l'APK est produit et installe;
- le telephone arm64 apparait en etat `device`;
- `:app:connectedDebugAndroidTest` passe sur ce telephone;
- les preuves de versions, installation, logs et hash sont conservees;
- la meme procedure est reproductible par une seconde personne.
