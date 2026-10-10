# Contrat JNI NativePHP

Ce contrat est la frontière entre le shell Kotlin et le runtime NativePHP. La bibliothèque native est fournie par le runtime hôte. Le shell ne fournit aucun double de production et ne bascule jamais vers HTML ou une autre implémentation.

## Bibliothèque

- Nom chargé par défaut: `nativephp`.
- Chargement obligatoire par `System.loadLibrary("nativephp")`.
- Absence de bibliothèque: `NativePhpLibraryUnavailableException`.
- ABI JNI obligatoire: `nativephpCall(String request): String`.
- ABI JNI obligatoire pour l'annulation: `nativephpCancel(String requestId): Boolean`.
- La bibliothèque doit être empaquetée par le runtime hôte pour chaque ABI publiée (`x86_64` et `arm64-v8a`).

## Requête

Le shell transmet un objet JSON. `request_id` est un UUID généré par Kotlin et ne doit pas être modifié par le runtime:

```json
{
  "request_id": "550e8400-e29b-41d4-a716-446655440000",
  "payload": {
    "node_id": "submit",
    "event": "click"
  }
}
```

Le runtime hôte doit transmettre le `payload` au runtime PHP et conserver `request_id` jusqu'à la réponse ou la fermeture de la requête.

## Réponse réussie

```json
{
  "request_id": "550e8400-e29b-41d4-a716-446655440000",
  "status": "ok",
  "result": {
    "accepted": true
  }
}
```

## Réponse en erreur

```json
{
  "request_id": "550e8400-e29b-41d4-a716-446655440000",
  "status": "error",
  "error": {
    "code": "PHP_ERROR",
    "message": "The PHP callback failed."
  }
}
```

Codes attendus:

- `PHP_ERROR`: erreur retournée par l'exécution PHP;
- `CAPABILITY_UNKNOWN`: capacité NativePHP inconnue;
- `BRIDGE_UNAVAILABLE`: runtime PHP indisponible;
- `INVALID_REQUEST`: enveloppe ou payload invalide;
- `TIMEOUT`: requête expirée côté hôte.

Toute réponse sans `request_id`, avec un identifiant différent, un statut inconnu ou une erreur incomplète est rejetée par `NativePhpInvalidResponseException`.

## Threading et cycle de vie

- `nativephpCall` est toujours appelé par le worker du transport, jamais par le main thread Android.
- `nativephpCancel` doit fermer la requête côté runtime hôte et libérer ses ressources.
- Un timeout Kotlin annule le worker, appelle `nativephpCancel` et notifie une seule fois `NativePhpTimeoutException`.
- Une annulation explicite notifie `NativePhpCancelledException`.
- La destruction du transport annule toutes les requêtes en attente et notifie `NativePhpInterruptedException`.
- Une réponse arrivée après fermeture, timeout ou annulation est ignorée.

## Validation réelle

Les tests JVM utilisent un invoker injecté uniquement pour vérifier le protocole et les erreurs. Ils ne constituent pas une preuve JNI.

La preuve finale exige la bibliothèque NativePHP réelle, le symbole `nativephpCall`, le symbole `nativephpCancel`, puis:

```powershell
.\gradlew.bat :app:connectedDebugAndroidTest --no-daemon --console plain
```

sur un émulateur `x86_64` et un appareil `arm64-v8a`. Une exécution sans la bibliothèque réelle ne doit pas être déclarée comme une validation JNI.
