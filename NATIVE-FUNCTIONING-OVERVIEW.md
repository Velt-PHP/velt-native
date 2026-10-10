# Fonctionnement actuel de Velt Native

## Vue simple

Velt Native est la partie qui permet à une application Velt de fonctionner sur Android avec une interface Compose native.

Le Kernel est le coeur portable de l’application. Il ne connaît pas Android et peut être utilisé par plusieurs environnements, par exemple le web, la CLI ou Android.

La relation est à sens unique:

```text
velt-native  --->  veltphp-kernel
```

Le Kernel ne dépend jamais de `velt-native`.

## Rôle du Kernel

Le Kernel s’occupe de la vie interne de l’application:

- démarrer l’application;
- indiquer quand elle est prête;
- recevoir une interaction;
- mettre l’application en pause;
- la reprendre;
- réinitialiser les états temporaires;
- arrêter définitivement l’instance;
- isoler les états applicatif, requête et session preview;
- signaler une erreur fatale au runtime hôte.

Le Kernel fournit donc les règles communes, mais il ne démarre pas Android, ne crée pas de thread Android et ne manipule aucune `Activity` ou `Context`.

## Rôle de Velt Native

Velt Native adapte ces règles au monde Android.

Il s’occupe de:

- communiquer avec le Kernel via `NativeKernelRuntime`;
- exposer les capacités Android au PHP;
- recevoir les documents UI produits par PHP;
- afficher ces documents avec Compose;
- transmettre les clics et interactions vers PHP;
- gérer le transport JNI;
- gérer les threads, l’APK et les bibliothèques Android;
- réagir aux événements Android comme la rotation ou le passage en arrière-plan.

## Comment les deux communiquent

### Démarrage

1. Android lance l’application Velt Native.
2. Le runtime Android démarre PHP sur un worker.
3. PHP crée l’application du Kernel.
4. `NativeKernelRuntime` utilise cette application.
5. Le Kernel exécute son démarrage et devient prêt.

### Affichage d’une page

1. Le Kernel ou le runtime PHP prépare un document UI Velt.
2. Velt Native reçoit ce document.
3. Compose transforme les nœuds Velt en composants Android.
4. L’utilisateur voit une interface Android native, sans WebView.

### Interaction utilisateur

1. L’utilisateur appuie sur un bouton.
2. Compose crée un événement avec l’identifiant du nœud.
3. Le transport Kotlin prépare une requête avec un identifiant unique.
4. JNI transmet la requête à PHP.
5. PHP transmet l’interaction au Kernel.
6. Le Kernel exécute l’interaction dans un scope de requête.
7. PHP renvoie une réponse au même identifiant.
8. Velt Native met à jour l’interface Compose.

### Cycle de vie Android

Lorsqu’Android met l’application en pause ou la recrée:

```text
Android pause       -> Velt Native -> Kernel pause
Android reprise     -> Velt Native -> Kernel resume
Nouvelle interaction -> Velt Native -> Kernel handle
Erreur irrécupérable -> Kernel demande une reconstruction au runtime
```

Le Kernel ne redémarre pas lui-même. Velt Native doit créer une nouvelle instance lorsque le Kernel le demande.

## Ce qui fonctionne déjà

- Les contrats de cycle de vie du Kernel sont présents et testés.
- `velt-native` dépend du Kernel, mais le Kernel ne dépend pas de `velt-native`.
- `NativeKernelRuntime` délègue les opérations au Kernel.
- Les capacités PHP Native sont définies avec des erreurs typées.
- Le protocole UI Velt est défini et validé côté PHP.
- Le shell Android Compose se compile.
- Les tests Kotlin et PHP passent.
- Les tests instrumentés du shell passent sur un téléphone arm64.
- Le transport Kotlin possède déjà une structure pour les identifiants, réponses, erreurs, timeout et annulation.

## Ce qui manque pour terminer Velt Native

### 1. Runtime PHP Android réel

L’application doit embarquer le moteur PHP Android et l’extension NativePHP. Pour le moment, le shell Android existe, mais il ne contient pas encore ce runtime.

### 2. Bibliothèque JNI NativePHP

Il faut fournir la vraie bibliothèque native avec les fonctions qui permettent à Kotlin de parler à PHP:

- `nativephpCall`;
- `nativephpCancel`.

Cette bibliothèque doit être disponible pour le téléphone arm64 et l’émulateur x86_64.

### 3. Démarrage réel du Kernel

Le runtime Android doit démarrer PHP, créer `NativeKernelRuntime`, appeler le bootstrap du Kernel et transmettre les interactions réelles.

### 4. Test de bout en bout

Il faut prouver le chemin complet:

```text
Compose -> Kotlin -> JNI -> PHP -> Kernel -> PHP -> JNI -> Kotlin -> Compose
```

Ce test doit être exécuté sur:

- un téléphone arm64;
- un émulateur x86_64.

### 5. Tests Android complets

Il reste à valider avec le runtime réel:

- callback PHP réussi;
- erreur PHP;
- capability inconnue;
- bridge absent;
- réponse invalide;
- timeout;
- annulation;
- interruption;
- rotation;
- background/foreground;
- reconstruction après erreur fatale.

## État actuel en une phrase

Le Kernel et le shell Android communiquent déjà au niveau des contrats, mais Velt Native n’est pas encore terminé car le moteur PHP Android et la bibliothèque JNI NativePHP réels ne sont pas encore intégrés dans l’APK.

## Condition de fin

Velt Native sera terminé lorsque le même APK pourra:

1. démarrer PHP sur Android;
2. démarrer le Kernel Velt;
3. afficher un document UI Compose;
4. envoyer une interaction vers PHP via JNI;
5. exécuter cette interaction dans le Kernel;
6. retourner la réponse à Compose;
7. réussir ces tests sur arm64 et x86_64 sans fake ni WebView.
