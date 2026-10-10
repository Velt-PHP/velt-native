# Retest Android sur un autre telephone

```powershell
Set-Location "C:\Users\semka\Desktop\travail\velt\velt-native\android"

$env:JAVA_HOME = "C:\Program Files\Microsoft\jdk-17.0.20.101-hotspot"
$env:ANDROID_HOME = "$env:LOCALAPPDATA\Android\Sdk"
$env:ANDROID_SDK_ROOT = $env:ANDROID_HOME
$env:Path = "$env:JAVA_HOME\bin;$env:ANDROID_HOME\platform-tools;$env:ANDROID_HOME\cmdline-tools\latest\bin;$env:Path"

java -version
adb version
.\gradlew.bat --version

adb kill-server
adb start-server
adb devices -l

$devices = @(adb devices | Select-String "\sdevice$")
if ($devices.Count -ne 1) {
    throw "Connecter exactement un telephone autorise, puis relancer cette commande."
}

$serial = ($devices[0].ToString() -split "\s+")[0]
$adb = "$env:ANDROID_HOME\platform-tools\adb.exe"
$apk = (Resolve-Path .\app\build\outputs\apk\debug\app-debug.apk -ErrorAction SilentlyContinue)

& $adb -s $serial shell getprop ro.product.model
& $adb -s $serial shell getprop ro.build.version.sdk
& $adb -s $serial shell getprop ro.product.cpu.abilist

if (-not $apk) {
    .\gradlew.bat :app:assembleDebug --no-daemon --console plain
    $apk = Resolve-Path .\app\build\outputs\apk\debug\app-debug.apk
}

.\gradlew.bat :app:test --no-daemon --console plain
.\gradlew.bat :app:assembleDebug --no-daemon --console plain

& $adb -s $serial uninstall com.velt.nativeapp 2>$null
& $adb -s $serial install $apk
if ($LASTEXITCODE -ne 0) { throw "Installation APK echouee." }

& $adb -s $serial shell pm path com.velt.nativeapp
& $adb -s $serial shell monkey -p com.velt.nativeapp 1

& $adb -s $serial logcat -c
.\gradlew.bat :app:connectedDebugAndroidTest --no-daemon --console plain
if ($LASTEXITCODE -ne 0) { throw "Tests instrumentes echoues." }

Get-FileHash $apk -Algorithm SHA256
& $adb -s $serial shell getprop ro.product.model
& $adb -s $serial shell getprop ro.build.version.sdk
& $adb -s $serial shell getprop ro.product.cpu.abilist
& $adb -s $serial shell pm path com.velt.nativeapp
& $adb -s $serial logcat -d -v threadtime | Select-String "FATAL EXCEPTION|AndroidRuntime|com.velt.nativeapp" | Out-File .\validation-logcat-$serial.txt
```

