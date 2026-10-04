# Issues restantes pour finaliser `velt/native`

Date de decomposition: 2026-10-01

## Contexte

Les contrats Kernel necessaires sont deja implementes et testes dans `veltphp-kernel`. Les issues ci-dessous concernent principalement `velt/native` et son environnement Android. Elles ne doivent pas ajouter de responsabilite Android au Kernel.

Etat de reference:

- Kernel: `192 tests, 393 assertions, OK`; PHPStan `No errors`.
- Native PHP: `19 tests, 1050 assertions, OK`.
- Contrats PHP de capacites et d'UI: presents.
- Shell Compose: present mais non valide par un build Android dans l'environnement actuel.
- JNI NativePHP, Gradle executable et preuves instrumentees: manquants.

## Ordre et dependances

```text
NATIVE-01 -> NATIVE-02 -> NATIVE-03
                         -> NATIVE-04
NATIVE-03 + NATIVE-04 -> NATIVE-05 -> NATIVE-06
KERNEL-01 accompagne NATIVE-02 et NATIVE-03 sans modifier le Kernel par defaut
```

---

## NATIVE-01 - Rendre le build Android reproductible

### Objectif

Permettre a une seconde personne de compiler, tester et installer le shell Android depuis un clone propre.

### Perimetre

- `velt/native/android` uniquement.
- JDK 17, Android SDK API 35, Gradle 8.9 et dependances Android epinglees.

### Criteres d'acceptation

- [ ] `gradlew`, `gradlew.bat` et `gradle-wrapper.jar` sont versionnes.
- [ ] `./gradlew --version` fonctionne depuis un clone propre.
- [ ] `./gradlew :app:test` passe.
- [ ] `./gradlew :app:assembleDebug` produit un APK installable.
- [ ] Le manifeste est valide et ne declare que les permissions necessaires.
- [ ] Les versions JDK, Gradle, AGP, Kotlin, Compose, SDK et ABI sont documentees.
- [ ] Aucun chemin local, repository `dev-main` ou dependance non resolue n'est utilise.

### Preuves

- Rapport des versions.
- Log complet du build.
- Hash de l'APK debug.
- Installation depuis un clone propre.

### Dependances

Aucune dependance fonctionnelle, mais requiert JDK 17, Android SDK et acces aux repositories Maven officiels.

---

## NATIVE-02 - Implementer le transport JNI NativePHP reel

### Objectif

Relier le transport Kotlin a l'execution PHP NativePHP reelle sans fake ni fallback silencieux.

### Perimetre

- `JniNativePhpTransport`.
- Bibliotheque native NativePHP et symbole `nativephpCall`.
- Enveloppe de requete/reponse entre Kotlin et PHP.

### Criteres d'acceptation

- [ ] La bibliotheque JNI est chargee explicitement et son absence produit une erreur typee.
- [ ] `nativephpCall` est implemente par le runtime hote et non par un double de test.
- [ ] Chaque requete possede un identifiant stable et unique.
- [ ] Chaque reponse indique son identifiant, son statut et son erreur eventuelle.
- [ ] Les reponses invalides, erreurs PHP, bridge indisponible et capability inconnue echouent explicitement.
- [ ] Les callbacks Kotlin vers PHP sont corréles a la requete d'origine.
- [ ] Les timeouts, annulations et interruptions ferment proprement la requete.
- [ ] Aucun appel JNI bloquant n'est execute sur le main thread.

### Tests

- [ ] Test JNI reel sur emulateur x86_64.
- [ ] Test JNI reel sur appareil arm64.
- [ ] Test bridge absent.
- [ ] Test reponse invalide.
- [ ] Test erreur PHP.
- [ ] Test timeout, annulation et interruption.
- [ ] Test callback reussi et callback en erreur.

### Dependances

NATIVE-01 et la documentation/API NativePHP validee.

---

## NATIVE-03 - Executer PHP sur un worker Android et gerer le cycle de vie

### Objectif

Executer le Kernel hors du main thread et relier correctement le cycle de vie Android aux contrats portables.

### Criteres d'acceptation

- [ ] Le worker PHP est cree sur un thread dedie.
- [ ] `boot` et `ready` sont emis dans l'ordre et une interaction n'est acceptee qu'apres `ready`.
- [ ] `pause`, `resume`, `reset` et `shutdown` sont transmis sans transition implicite.
- [ ] Une instance shutdown n'est jamais reutilisee.
- [ ] `runtime.rebuild.requested` arrete proprement le worker et demande une nouvelle instance au runtime hote.
- [ ] L'`Activity` et le `Context` ne sont jamais places dans le container durable du Kernel.
- [ ] Rotation, background/foreground et recreation du processus ne provoquent ni fuite ni double worker.
- [ ] Les callbacks sont ignores ou annules apres destruction du worker.

### Tests

- [ ] Tests de cycle de vie avec rotation.
- [ ] Tests background/foreground.
- [ ] Test recreation du processus.
- [ ] Test erreur fatale et reconstruction.
- [ ] Test absence d'`Activity`/`Context` dans les objets persistants.
- [ ] Test de concurrence et absence d'appel PHP sur le main thread.

### Dependances

NATIVE-02 et les contrats existants du Kernel. Aucun changement Kernel ne doit etre ajoute sauf incompatibilite demontree par un test.

---

## NATIVE-04 - Finaliser le renderer Compose et la navigation

### Objectif

Rendre le document UI PHP avec des composants Material reels et sans WebView.

### Criteres d'acceptation

- [ ] Chaque type du protocole est rendu par un composant Compose dedie.
- [ ] `Image` utilise une source Android valide et echoue explicitement si elle est invalide.
- [ ] `Icon` supporte le catalogue defini par le protocole ou renvoie `capability_not_available`.
- [ ] Les props et tokens PHP sont appliques au renderer et au `MaterialTheme`.
- [ ] Les modes clair et sombre sont verifies par snapshots.
- [ ] Les identifiants, cles et metadonnees d'accessibilite sont conserves dans l'arbre Compose.
- [ ] Les interactions `Button`, `Pressable`, `Input`, `Toggle`, `List` et `ListItem` emettent des callbacks PHP.
- [ ] `push`, `replace` et `back` sont relies a un `NavHost` ou un equivalent Android teste.
- [ ] Un nœud inconnu ou invalide produit un diagnostic explicite.
- [ ] Aucun ecran principal ne depend d'une WebView ou d'un rendu HTML.

### Tests

- [ ] Test Compose de chaque type de nœud.
- [ ] Snapshots clair et sombre.
- [ ] Test des callbacks et de l'accessibilite.
- [ ] Test de navigation.
- [ ] Test de nœud inconnu et de props invalides.
- [ ] Test instrumente confirmant l'absence de WebView.

### Dependances

NATIVE-01 et NATIVE-02. Peut etre developpe en parallele de NATIVE-03, mais la validation E2E depend des deux.

---

## NATIVE-05 - Valider le runtime sur emulateur et appareil

### Objectif

Fournir les preuves instrumentees du fonctionnement complet Native.

### Criteres d'acceptation

- [ ] Une page UI PHP complete est rendue sur emulateur x86_64.
- [ ] La meme page est rendue sur un appareil ou device farm arm64.
- [ ] Les interactions Compose atteignent PHP et les reponses PHP atteignent Compose.
- [ ] 1 000 interactions consecutives passent sans fuite ni blocage.
- [ ] Rotation et recreation de processus conservent ou reconstruisent l'etat attendu.
- [ ] Background/foreground et interruption annulent proprement les travaux.
- [ ] Pression memoire et reprise sont testees.
- [ ] Boot, latence appel/callback, memoire et ANR sont exportes.
- [ ] Aucun ecran principal n'est rendu avec WebView.

### Preuves

- [ ] Logs instrumentes complets.
- [ ] Captures ou snapshots Compose.
- [ ] Rapport de memoire et de latence.
- [ ] Resultat emulator x86_64.
- [ ] Resultat device arm64.

### Dependances

NATIVE-01, NATIVE-02, NATIVE-03 et NATIVE-04.

---

## NATIVE-06 - Qualifier et publier les artefacts Android

### Objectif

Transformer les preuves de test en artefacts verifiables et publiables.

### Criteres d'acceptation

- [ ] APK debug et release produits depuis un build reproductible.
- [ ] AAB produit si le pipeline de publication le requiert.
- [ ] Hashes des artefacts publies.
- [ ] SBOM et provenance des dependances generes.
- [ ] Les namespaces et doubles de test sont absents des artefacts release.
- [ ] Les permissions du manifeste sont auditees.
- [ ] Une installation propre est validee.
- [ ] La documentation de migration et les limites connues sont a jour.
- [ ] Une seconde personne reproduit le build et les tests.

### Dependances

NATIVE-05 et la CI Android de publication.

---

## KERNEL-01 - Gate de compatibilite Kernel/Native

### Nature

Issue de validation inter-repos, pas une nouvelle implementation Kernel.

### Objectif

Garantir que la version du Kernel consommee par Native respecte les contrats publics sans ajouter de couplage Android.

### Criteres d'acceptation

- [ ] Native utilise une version distribuee du Kernel, et non un repository `path`, avant release.
- [ ] Les tests Kernel et Native passent avec la meme version resolue.
- [ ] `NativeKernelRuntime` delegue toutes les transitions sans redefinir la machine d'etat.
- [ ] Les scopes applicatif, requete et preview conservent leurs responsabilites respectives.
- [ ] `runtime.rebuild.requested` est consomme par le runtime Native, pas par le Kernel.
- [ ] Aucun type Android n'est introduit dans les interfaces Kernel.
- [ ] Une verification CI inter-repos bloque les incompatibilites de contrat.

### Decision actuelle

Aucun changement de code Kernel n'est requis tant qu'un test Native ou une gate CI ne montre pas une incompatibilite. Cette issue sert uniquement de gate de compatibilite et de release.

---

## Definition de fin globale

L'implementation Native pourra etre declaree terminee lorsque NATIVE-01 a NATIVE-06 et KERNEL-01 sont valides, les tests PHP et Android sont verts, les artefacts sont verificables et une seconde personne peut reproduire le resultat depuis un clone propre.
