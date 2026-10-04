# Etat du fonctionnement de `velt/native`

Date de l'audit: 2026-10-01

Ce document distingue les fondations fournies par `veltphp-kernel`, le code Native PHP present dans ce depot et les elements qui necessitent encore un runtime Android reel.

## Resume

- Fonctionnement PHP Native local: **realise et teste**.
- Adaptation Native du Kernel: **presente et testee**.
- Contrat UI PHP et shell Compose: **presentes, mais non valides par build Android ici**.
- Bridge JNI/NativePHP reel: **manquant**.
- Fonctionnement Android complet: **non finalise**.

Le Kernel est suffisamment en place pour etre consomme par Native. Il ne fournit volontairement ni thread Android, ni JNI, ni gestion d'Activity, ni renderer Compose.

## Ce que le Kernel fournit deja

| Besoin Native | Etat dans `veltphp-kernel` | Utilisation par Native |
| --- | --- | --- |
| Cycle de vie `boot`, `ready`, `pause`, `resume`, `reset`, `shutdown` | Realise par `RuntimeInterface`, `Application` et `RuntimeState` | `NativeKernelRuntime` delegue les transitions |
| Etats `bootstrapped`, `ready`, `paused`, `terminated`, `shutdown` | Realises avec transitions invalides deterministes | Exposes par l'adaptateur Native |
| Services applicatifs persistants | `ApplicationScopeInterface` et `ApplicationScope` | Survivent aux resets du Kernel |
| Etat de requete | `RequestScopeInterface` et `RequestScope` | Cree et nettoye par interaction |
| Etat de session preview | `PreviewSessionScopeInterface` et `PreviewSessionScope` | Resettable et destructible sans casser les singletons |
| Execution abstraite | `ExecutionQueueInterface`, `ExecutionTaskInterface`, annulation et expiration | L'implementation thread reste a l'hote Android |
| Erreur fatale | `RuntimeFailureInterface`, nettoyage et evenement `runtime.rebuild.requested` | Native doit reconstruire l'instance |
| Migrations, cache, configuration, evenements | Contrats generiques disponibles | Implementations plateforme hors Kernel |
| Absence de dependance Android | Respectee | Native ne passe pas `Activity` ou `Context` au Kernel |

Validation actuelle du Kernel:

- PHPUnit: `192 tests, 393 assertions, OK`.
- PHPStan: `No errors`.
- Les contrats Kernel ne redemarrent jamais eux-memes le runtime.

## Ce qui fonctionne deja dans `velt/native`

### Bridge et capacites PHP

- `NativePhpBridge` appelle `nativephp_call()` quand l'extension est disponible.
- L'indisponibilite du bridge echoue explicitement, sans fake de production.
- `Device` couvre informations appareil, vibration et lampe torche.
- `Dialog` couvre dialogues et toasts.
- `SandboxFileStore` couvre lecture, ecriture et suppression dans des chemins relatifs securises.
- `NativeCapabilityException` transporte les erreurs typees comme `permission_denied`, `invalid_native_response` et `invalid_path`.
- `FakeNativeBridge` est reserve aux tests PHP hors appareil.

### Adaptation du Kernel

- `NativeKernelRuntime` implemente `RuntimeInterface`.
- Les appels de cycle de vie sont delegues a `Velt\Kernel\Application`.
- L'adaptateur ne conserve aucune reference `Activity` ou `Context`.
- Les metriques PHP couvrent boot, memoire, appels, callbacks et ANR signales par l'hote.
- Le test local couvre 1 000 interactions PHP.

### Contrat UI

- `UiProtocol` negocie la version `1`.
- `UiDocument` et `UiNode` imposent type, identifiant et cle stables.
- Les nuds, l'accessibilite et les props sont serialisables.
- `UiTheme` fournit les modes clair/sombre et tokens portables.
- `UiNavigation` fournit `push`, `replace` et `back`.
- Le shell Android contient un decodeur Kotlin et un renderer Compose sans WebView.

Validation actuelle de Native PHP:

- PHPUnit: `19 tests, 1050 assertions, OK`.
- `composer validate --strict`: OK.
- `composer check-platform-reqs`: OK.
- `git diff --check`: OK.

## Ce qui manque pour le fonctionnement Native complet

### 1. Build Android reproductible

- Ajouter le wrapper Gradle complet: `gradlew`, `gradlew.bat` et `gradle-wrapper.jar`.
- Executer le build avec JDK 17, Gradle 8.9 et Android SDK API 35.
- Verifier `:app:test`, `:app:assembleDebug` et `:app:connectedCheck` depuis un clone propre.
- Produire un rapport des versions, dependances et artefacts.

Etat local au moment de l'audit:

- JDK 17 detecte.
- Gradle non detecte.
- ADB non detecte.
- Wrapper Gradle incomplet.

### 2. Bridge JNI reel

- Implementer le symbole JNI `nativephpCall` cote runtime NativePHP.
- Charger la bibliotheque native avec une gestion d'erreur explicite.
- Relier le transport Kotlin a l'execution PHP reelle.
- Definir et figer l'enveloppe requete/reponse, les identifiants de requete et les erreurs.
- Tester bridge absent, erreur PHP, timeout, annulation, interruption et callback.

### 3. Execution et cycle de vie Android

- Executer PHP sur un thread dedie hors main thread.
- Connecter `boot`, `ready`, `pause`, `resume`, `reset` et `shutdown` aux callbacks Android.
- Reagir a rotation, background/foreground et recreation de processus.
- Ne jamais placer `Activity` ou `Context` dans le container durable du Kernel.
- Creer une nouvelle instance apres `runtime.rebuild.requested` au lieu de redemarrer le Kernel existant.

### 4. Renderer Compose complet

- Executer les tests Kotlin et Compose pour tous les nuds declares.
- Remplacer les rendus provisoires d'images et d'icones par les sources Android reelles.
- Appliquer effectivement les tokens PHP au `MaterialTheme`.
- Relier `push`, `replace` et `back` a un `NavHost` Android.
- Ajouter snapshots clair/sombre.
- Verifier qu'aucune WebView n'est utilisee pour l'ecran principal.

### 5. Preuves Android

- Test instrumente sur emulateur x86_64.
- Test sur appareil ou device farm arm64.
- Installation propre de l'APK.
- Rendu et interaction d'une page complete.
- Callback Compose vers PHP et retour PHP vers Compose.
- Tests de permission, erreur, timeout, interruption et reprise.
- Tests de memoire, rotation et background/foreground.
- Hash, SBOM, provenance et verification d'absence des namespaces de test dans la release.

## Reponse a la question Kernel

Oui, les fondations Kernel necessaires a `velt/native` sont en place et couvertes par les tests PHP. Le Kernel fournit les contrats et l'etat; il ne doit pas fournir les threads, JNI, permissions Android ou rendu Compose.

La chaine Native n'est toutefois pas encore complete: l'adaptateur PHP existe, mais le runtime Android reel qui doit l'executer, le bridge JNI, le build reproductible et les preuves instrumentees restent a finaliser.

## Ordre recommande de finalisation

1. Installer/configurer JDK 17, Android SDK, Gradle 8.9, ADB et un emulateur.
2. Ajouter et valider le wrapper Gradle depuis un clone propre.
3. Integrer le runtime NativePHP et implementer `nativephpCall`.
4. Executer les tests unitaires Kotlin et corriger le renderer Compose.
5. Ajouter les snapshots et les tests instrumentes x86_64.
6. Reproduire les tests sur arm64.
7. Publier les artefacts et preuves, puis seulement cloturer l'issue.

## Conclusion

Le Kernel est pret pour l'integration Native au niveau contractuel. `velt/native` possede maintenant les contrats PHP, l'adaptateur Kernel et une base Compose, mais ne peut pas encore etre declare fonctionnel sur Android tant que le JNI reel, le build Android et les tests instrumentes n'ont pas ete executes.
