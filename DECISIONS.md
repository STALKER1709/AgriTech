# DECISIONS.md — journal des décisions

Chaque entrée indique la date, la décision, sa justification et les alternatives
écartées. Les hypothèses prises faute de réponse y figurent également, signalées
comme telles.

---

## Phase 0 — Installation et socle

### 2026-09-19 — Décisions produit tranchées par le porteur du projet

| Sujet | Décision | Justification |
|---|---|---|
| Environnement local | **Laragon** (valeur par défaut retenue) | Poste de développement Windows ; Laragon fournit PHP, MySQL/MariaDB et un terminal sans configuration. |
| Base de données | **MySQL 8 / MariaDB** | Moteur transactionnel réel, indispensable pour tester `lockForUpdate` (RG04). Alternative écartée : SQLite, qui ne reproduit pas le verrouillage de lignes et rendrait le test de concurrence sur le stock trompeur. |
| Contexte | **Prototype commercial sérieux** | Priorité à la robustesse, la sécurité et la maintenabilité ; documentation concise et orientée développeur plutôt qu'académique. |
| Panier multi-agriculteurs | **Oui**, commande scindée en `SubOrder` par agriculteur | Chaque agriculteur gère sa propre livraison et son propre statut. |
| Livraison | Gérée par l'agriculteur, suivie par statut de sous-commande | Aucun partenaire logistique à intégrer en local. |
| Format des formations | Fichiers vidéo et PDF stockés en local, servis par un contrôleur protégé | Respecte l'interdiction des services externes et la RG05. |
| Messagerie | Asynchrone, rafraîchissement par polling Livewire | Évite d'ajouter un serveur WebSocket (Reverb/Pusher), hors périmètre local. |
| Contenu de l'abonnement | Accès illimité aux formations marquées `included_in_subscription` | Valeur par défaut du cahier des charges. |
| Frais d'inscription agriculteur | **10 000 FCFA**, paiement unique, remboursement manuel par l'admin en cas de refus | Voir la réserve ci-dessous. |
| Commission de la plateforme | **5 %** sur les ventes de produits et de formations | Voir la réserve ci-dessous. |

#### Réserve explicite sur les montants

Ces deux montants ont été demandés « en accord avec les standards du marché ».
**Aucune source vérifiable et à jour sur les tarifs pratiqués par les plateformes
agricoles camerounaises n'a pu être citée.** Les valeurs retenues reposent sur
des ordres de grandeur généraux et doivent être validées auprès d'acteurs du
terrain avant toute exploitation réelle :

- 10 000 FCFA se veut une barrière à l'entrée dissuasive contre les faux comptes
  sans être prohibitive pour un petit producteur. Le raisonnement s'appuie sur un
  SMIG camerounais supposé proche de 41 875 FCFA par mois — **chiffre non
  confirmé, susceptible d'avoir évolué**.
- 5 % correspond au bas de la fourchette habituelle des places de marché
  e-commerce (usuellement 5 % à 20 % selon la catégorie), cohérent avec des
  produits agricoles à faible marge. **Aucune source spécifique au Cameroun
  n'est citée.**

Les deux valeurs sont stockées dans la table `Setting` et modifiables depuis
l'écran d'administration (phase 4) : les corriger ne demandera aucune
modification de code.

Note connexe : les opérateurs Mobile Money prélèvent leurs propres frais (de
l'ordre de 1 % à 2 %, **chiffre incertain**). Sans impact technique ici puisque
la passerelle est simulée.

---

### 2026-09-19 — Environnement de développement distant sous Linux

**Décision.** Le développement et les tests sont réalisés dans un conteneur
Linux distant ; le porteur du projet clone la branche et valide chaque phase sur
son poste Windows avec Laragon.

**Conséquences assumées :**

- Le `README.md` reste entièrement orienté Windows et n'a **pas pu être exécuté
  tel quel** dans cet environnement. Les comportements spécifiques à Windows
  (notamment `php artisan storage:link`, qui crée un lien symbolique) sont
  documentés mais **non vérifiés**.
- Un serveur **MariaDB 10.11** a été installé dans le conteneur, avec les bases
  `agritech` et `agritech_test`, afin que les tests s'exécutent sur un moteur
  transactionnel réel.
- Sensibilité à la casse et fins de ligne : `.gitattributes` impose
  `* text=auto eol=lf`, ce qui neutralise les divergences CRLF/LF.

---

### 2026-09-19 — Laravel 13.32 et PHP 8.4

**Décision.** Laravel 13.32.0, PHP 8.4.

**Justification.** Laravel 13 exige PHP `^8.3`, mais **Pest 5 exige PHP `^8.4`**.
Puisque Pest est la stack de test demandée, PHP 8.4 devient le plancher réel du
projet. Le porteur du projet doit donc disposer de PHP 8.4 dans Laragon.

**Alternative écartée.** Rester en PHP 8.3 en utilisant PHPUnit seul : aurait
privé le projet de la syntaxe Pest demandée au cahier des charges.

---

### 2026-09-19 — Starter kit d'authentification : Livewire Starter Kit officiel

**Décision.** Utilisation du starter kit Livewire officiel de Laravel, dans sa
**variante « composants de classe »** (branche `components` du dépôt
`laravel/livewire-starter-kit`), qui s'appuie sur **Laravel Fortify**.

**Justification.**
- C'est le starter kit recommandé par Laravel pour Livewire (option `--livewire`
  de l'installateur officiel, confirmée par `laravel new --help`).
- Fortify fournit l'ossature serveur (connexion, inscription, réinitialisation
  du mot de passe, vérification d'e-mail) sans imposer de vues, ce qui laisse
  la liberté de construire une interface française et mobile-first.
- La variante « composants de classe » place les composants dans
  `app/Livewire/`, conforme à l'arborescence exigée. La variante par défaut
  utilise des composants mono-fichier dont les noms contiennent un **emoji ⚡** :
  risqué sous Windows, dans git et dans les outils en ligne de commande.

**Alternative écartée.** Laravel Breeze et Jetstream : toujours maintenus et
compatibles Laravel 13, mais ce ne sont plus les starter kits mis en avant, et
Jetstream embarque des fonctionnalités (équipes, gestion d'API) hors périmètre.

---

### 2026-09-19 — Fonctionnalités d'authentification retenues

**Décision.** Sont activées : **inscription**, **réinitialisation du mot de
passe**, **vérification d'e-mail**, **confirmation du mot de passe**.
Sont désactivées : **authentification à deux facteurs** et **passkeys**.

**Justification.** 2FA et passkeys ne figurent pas au cahier des charges. Les
conserver aurait ajouté des migrations, des écrans et des dépendances
(`web-auth/webauthn-lib`, `pragmarx/google2fa`) à maintenir sans bénéfice
demandé. La confirmation du mot de passe est conservée : elle coûte peu et
protégera les actions administratives sensibles (RG07).

**Conséquence.** Le code mort laissé par la désactivation a été retiré :
méthode `UserFactory::withTwoFactor()` et test
`AuthenticationTest::test_users_with_two_factor_enabled_are_redirected_to_two_factor_challenge`.

---

### 2026-09-19 — Paquets retirés du starter kit

| Paquet | Motif |
|---|---|
| `laravel/sail` | Environnement Docker — explicitement interdit par le cahier des charges. |
| `laravel/chisel` | Outil d'installation du starter kit, sans utilité une fois la sélection des fonctionnalités appliquée. |

Le workflow GitHub Actions livré par le starter kit (`.github/workflows/tests.yml`)
a également été supprimé : il cible PHP 8.3 et SQLite, incompatibles avec le
socle retenu (PHP 8.4, MariaDB), et aucune intégration continue n'a été
demandée. Il pourra être réintroduit et adapté sur demande.

---

### 2026-09-19 — `livewire/flux` conservé

**Décision.** La bibliothèque de composants d'interface **Flux 2** (`livewire/flux`),
dépendance du starter kit officiel, est conservée.

**Justification.** Il s'agit de la version **gratuite** de Flux : elle s'installe
sans clé de licence ni compte, et fonctionne hors ligne une fois installée. Elle
est construite sur Tailwind, donc cohérente avec la stack demandée.

**Point de vigilance.** Flux dispose d'une déclinaison payante (« Flux Pro »)
qui, elle, exigerait un compte. **Aucun composant Pro ne doit être utilisé**,
sous peine de rompre la contrainte « aucun service externe payant ».

---

### 2026-09-19 — Pest 5 et PHPUnit 13

**Décision.** `phpunit/phpunit` a été retiré des dépendances directes ; Pest 5
l'apporte lui-même en version 13.

**Justification.** Le starter kit épinglait PHPUnit `^12.5`, incompatible avec
Pest 5 qui exige PHPUnit `^13.3`. Laisser Pest piloter la version de PHPUnit
évite un conflit de contraintes à chaque mise à jour.

---

### 2026-09-19 — Base de tests dédiée sur moteur réel

**Décision.** `phpunit.xml` pointe sur la base **`agritech_test`** en MySQL/MariaDB,
et non sur SQLite en mémoire.

**Justification.** La RG04 impose un test de concurrence sur le stock avec
`lockForUpdate`. SQLite ne reproduit pas le verrouillage de lignes de MySQL : le
test passerait sans rien prouver. Le coût est une exécution un peu plus lente et
la nécessité de créer la base au préalable.

**Conséquence pour l'exécution en parallèle.** `pest --parallel` crée des bases
`agritech_test_test_1`, `agritech_test_test_2`… L'utilisateur MySQL doit donc
pouvoir créer et supprimer ces bases. Avec l'utilisateur `root` de Laragon, c'est
acquis. C'est documenté dans le `README.md`.

---

### 2026-09-19 — Fichiers de langue français, anglais retiré

**Décision.** `lang/fr/` (validation, auth, passwords, pagination) et
`lang/fr.json` sont fournis ; `lang/en/` a été supprimé.

**Justification.** `APP_LOCALE` et `APP_FALLBACK_LOCALE` valent tous deux `fr` :
conserver une traduction anglaise inutilisée aurait créé deux sources de vérité.
Les 111 clés de validation de Laravel 13 ont été traduites et leur correspondance
avec le fichier d'origine a été vérifiée automatiquement.

**Limite assumée.** Les vues livrées par le starter kit contiennent encore des
chaînes anglaises en dur (écrans de connexion, d'inscription, de réglages). Leur
traduction et leur passage par `__()` sont planifiés en **phase 2**, qui porte
précisément sur l'authentification et les comptes.

---

### 2026-09-19 — Contrainte propre au conteneur : installation Composer depuis les sources

**Décision.** Dans le conteneur distant, Composer est configuré en
`preferred-install: source`, et l'archive de `phpstan/phpstan` a été déposée
manuellement dans le cache Composer à partir d'un clone git superficiel.

**Justification.** La politique réseau de la session autorise `git clone` mais
bloque le téléchargement des archives via l'API GitHub (HTTP 403), dont dépend
le mode `dist` de Composer. `phpstan/phpstan` n'étant distribué qu'en `dist`, il
ne pouvait pas être installé autrement.

**Portée.** Cette contrainte est **strictement locale au conteneur**. Sur le
poste Windows, `composer install` fonctionne normalement en mode `dist` : aucune
configuration particulière n'est nécessaire, et `composer.json` n'a pas été
modifié pour en tenir compte.

**Effet de bord observé.** L'installation depuis les sources déclenche des
avertissements « Ambiguous class resolution » pour `league/flysystem`, car les
archives `dist` excluent des fichiers de test que le dépôt git contient. Ces
avertissements n'apparaissent pas sur une installation `dist` classique.

---

## Phase 1 — Modèle de données

### 2026-09-19 — Les lignes de commande s'attachent aux sous-commandes

**Décision.** `order_items.sub_order_id`, et non `order_id`.

**Justification.** Le panier est multi-agriculteurs : chaque ligne appartient
nécessairement à un seul agriculteur. Passer par la sous-commande rend ce lien
structurel plutôt que déductible, et supprime la possibilité qu'une ligne se
retrouve rattachée à une commande sans sous-commande correspondante. Le
`total_amount` de la commande est la somme des `subtotal_amount`, eux-mêmes
sommes de leurs lignes ; un test vérifie cette cohérence sur tout le jeu de
démonstration.

**Alternative écartée.** Lignes sur la commande avec un `farmer_id` dupliqué :
deux sources de vérité pour la même information.

---

### 2026-09-19 — Privilèges implémentés sans `spatie/laravel-permission`

**Décision.** Table `privileges` + pivot `privilege_user`, écrits à la main.

**Justification.** Le rôle est déjà une colonne enum sur `users` ; Spatie
apporterait ses propres tables de rôles, en doublon avec cette colonne, pour un
besoin qui tient en une table et un pivot. Le catalogue des privilèges est
déclaré dans `Privilege::catalogue()`, ce qui garde le seeder et l'écran
d'administration alignés sur une seule liste. Moins de dépendance à suivre,
comportement entièrement lisible dans le code du projet.

**Alternative écartée.** `spatie/laravel-permission` : excellent paquet, mais
dimensionné pour des besoins d'autorisation nettement plus riches que les huit
privilèges d'AgriTech.

---

### 2026-09-19 — La commission est figée sur la sous-commande

**Décision.** `sub_orders.commission_rate_snapshot` et
`sub_orders.commission_amount` sont enregistrés à la création de la commande.

**Justification.** Le taux est un paramètre modifiable par l'administrateur.
S'il était relu à l'affichage, changer le taux réécrirait rétroactivement la
valeur de toutes les commandes passées. Le figer préserve l'historique
comptable. Même raisonnement que `order_items.unit_price_snapshot`.

---

### 2026-09-19 — Objet-valeur `Quantity` plutôt que `bcmath`

**Décision.** Les quantités (`stock_quantity`, `order_items.quantity`) passent
par une classe `Quantity` qui stocke un **entier de millièmes**, sur le modèle
de `Money`.

**Justification.** La RG04 interdit tout risque d'arrondi sur les stocks. Trois
options se présentaient :

1. `float` — écartée d'emblée, c'est précisément ce que la règle proscrit ;
2. `bcmath` — ajoute une extension PHP obligatoire à installer sous Windows, et
   **n'est pas disponible dans l'environnement de développement** ;
3. un entier mis à l'échelle — aucune dépendance, aucune extension, comparaisons
   exactes, et parfaitement cohérent avec la façon dont l'argent est déjà traité.

La troisième a été retenue. La colonne reste `decimal(12,3)` comme prévu au
cahier des charges ; `QuantityCast` fait la conversion, et refuse un `float` à
l'écriture plutôt que de l'arrondir en silence.

**Conséquence documentée.** `bcmath` a été **retiré** de la liste des extensions
requises dans le `README.md`, où il figurait à tort.

---

### 2026-09-19 — Bug corrigé dans le formatage monétaire

Le premier jet de `Money::formatNumber()` groupait les milliers avec
`strrev(implode(..., str_split(strrev($digits), 3)))`. `strrev` inverse des
**octets**, pas des caractères : l'espace fine insécable U+202F (`E2 80 AF`)
ressortait inversé en `AF 80 E2`, soit une séquence UTF-8 invalide.

Le test de formatage l'a détecté immédiatement. Le groupage se fait désormais
par découpage depuis la droite, sans inversion de la chaîne assemblée. Les
montants s'affichent `12 500 FCFA` avec U+202F entre les groupes et U+00A0
avant la devise, pour qu'un retour à la ligne ne sépare jamais le montant de
« FCFA ».

---

### 2026-09-19 — Hypothèse sur le format des numéros camerounais

**Hypothèse retenue, à confirmer.** Les numéros sont stockés normalisés au
format international `+237XXXXXXXXX`, et les jeux de données utilisent des
mobiles à neuf chiffres commençant par 6.

**Réserve.** Je crois que la renumérotation de 2016 a porté les mobiles
camerounais à neuf chiffres préfixés par 6, **mais je ne peux pas le confirmer
par une source vérifiable**, et la règle a pu évoluer depuis. La contrainte
n'est pour l'instant appliquée qu'au niveau du stockage (colonne unique de 20
caractères) : **aucune expression régulière ne rejette encore de numéro**. La
règle de validation précise sera écrite en phase 2, une fois le format
confirmé. Le message d'erreur correspondant est déjà prévu dans
`lang/fr/validation.php`, sous `custom.phone.regex`.

---

### 2026-09-19 — La table `users` a été modifiée sur place

**Décision.** La migration d'origine `create_users_table` a été réécrite
(`first_name`, `last_name`, `phone`, `role`, `status`) plutôt que complétée par
une migration d'altération.

**Justification.** Rien n'est déployé et la base est recréée à volonté : un
historique de migrations qui ajoute des colonnes à une table livrée quelques
heures plus tôt serait une fiction. Un schéma lisible d'un seul tenant vaut
mieux.

**Conséquence.** La colonne `name` disparaît. Un accesseur `name` la
reconstitue (`first_name` + `last_name`), ce qui laisse fonctionner les vues du
starter kit sans modification. Les écrans d'inscription et de profil ont été
adaptés aux nouveaux champs, ainsi que leurs tests.

---

### 2026-09-19 — Détection des requêtes N+1 en local uniquement

**Décision.** `Model::preventLazyLoading(app()->environment('local'))`.

**Justification.** Transforme un chargement paresseux silencieux en exception
pendant le développement, là où le problème doit se voir. Volontairement limité
à `local` : cette protection ne doit jamais faire tomber une page devant un
utilisateur. Vérifié que `migrate:fresh --seed` passe avec cette option active.

---

### 2026-09-19 — Le jeu de démonstration est rejouable

**Décision.** Les données de référence et les comptes utilisent
`updateOrCreate` ; les transactions (commandes, paiements, achats, abonnement,
conversation) ne sont créées que si aucune commande n'existe déjà.

**Justification.** Un test qui exécute le seeder deux fois de suite a révélé une
violation de contrainte d'unicité sur `training_purchases`. Une commande est un
**événement**, pas une donnée à réconcilier : la rejouer n'a pas de sens. Le
seeder s'arrête donc avant cette section lorsqu'elle a déjà été jouée, ce qui
rend `php artisan db:seed` sûr à relancer. `migrate:fresh --seed` repart
évidemment d'une base vide et reproduit l'intégralité du jeu.

---

## Phase 2 — Authentification et comptes

### 2026-09-19 — Connexion par e-mail ou téléphone : un champ unique `login`

**Décision.** `config/fortify.php` passe de `'username' => 'email'` à
`'username' => 'login'`, et `Fortify::authenticateUsing()` résout la saisie :
une adresse e-mail si elle en a la forme, sinon un numéro normalisé.

**Justification.** Fortify n'accepte qu'un seul champ d'identification. Offrir
deux champs séparés aurait obligé l'utilisateur à choisir avant de saisir, pour
un bénéfice nul. Le service `UserLookup` porte cette résolution, testée pour
cinq écritures différentes du même numéro.

**Conséquences.**
- `lowercase_usernames` passe à `false` : mettre un numéro en minuscules n'a
  aucun sens. Les recherches par e-mail appliquent `mb_strtolower` elles-mêmes.
- **La réinitialisation du mot de passe reste par e-mail uniquement.** Le broker
  de Laravel a besoin d'une adresse pour envoyer le lien, et aucun canal SMS
  n'existe en local. Un utilisateur inscrit sans e-mail ne pourrait pas
  réinitialiser — le champ e-mail reste donc obligatoire.
- Les tests du starter kit qui postaient `email` ont été adaptés.

---

### 2026-09-19 — Un compte suspendu, refusé ou supprimé reçoit un message explicite

**Décision.** Le service `AccountAccess` refuse l'ouverture de session de ces
trois statuts, avec un message propre à chacun plutôt que « identifiants
incorrects ».

**Justification.** Renvoyer « identifiants incorrects » à quelqu'un dont les
identifiants sont corrects l'envoie tourner en rond : il ressaisira, réessaiera,
puis se fera bloquer par la limitation de débit. Le compromis de sécurité est
mince : il faut déjà connaître le mot de passe pour voir ce message.

**Choix associé.** Un agriculteur `pending_payment` ou `pending_validation`
**peut** se connecter. C'est nécessaire : c'est ainsi qu'il paie ses frais et
suit son dossier. C'est le middleware `account.active` qui lui ferme son espace
de travail, pas la connexion.

---

### 2026-09-19 — Limitation de débit : Fortify n'en pose que sur la connexion

**Constat.** En lisant les routes de Fortify, seules la connexion, la
double authentification, les passkeys et la vérification d'e-mail lisent une
clé `limiters`. **L'inscription et la demande de réinitialisation de mot de
passe ne sont limitées par rien.** Déclarer `'limiters.register' => …` aurait
donné un réglage sans aucun effet.

**Décision.** Un middleware `ThrottleSensitiveAuthRoutes`, ajouté à
`config('fortify.middleware')`, limite ces routes à cinq tentatives par minute
et par adresse IP, et s'efface immédiatement pour tout le reste.

**Décision associée.** `'limiters.login'` est volontairement laissé à `null`.
Renseigné, Fortify délègue au middleware `throttle`, qui répond une page 429
brute ; laissé vide, il utilise sa propre action `EnsureLoginIsNotThrottled`,
qui échoue en validation avec le message `auth.throttle` traduit, affiché sur
le formulaire. Même limite de cinq tentatives par minute, bien meilleure
expérience.

---

### 2026-09-19 — L'inscription agriculteur est un parcours distinct

**Décision.** Route, composant Livewire et service `FarmerRegistrar` propres,
séparés de l'inscription Fortify qui crée les clients.

**Justification.** Un compte agriculteur écrit **deux** enregistrements
(`User` + `FarmerProfile`) et démarre dans un état différent
(`pending_payment`). Les deux écritures sont dans une `DB::transaction()`, ce
qui exclut le compte sans profil ; un test le vérifie en provoquant un échec de
validation.

**Limite assumée et visible.** Le bouton de paiement de l'écran de statut est
**désactivé**, avec un texte qui dit explicitement que le paiement Mobile Money
n'est pas encore disponible. La passerelle simulée est le sujet de la phase 3,
qui branchera ce parcours jusqu'à `pending_validation` (RG02).

---

### 2026-09-19 — Numéros de téléphone : objet-valeur et hypothèse documentée

**Décision.** `Support\PhoneNumber` normalise tout vers `+237XXXXXXXXX`. Un
mutateur sur `User::phone` garantit que **toute** écriture passe par là.

**Justification.** Sans normalisation, la contrainte d'unicité sur `users.phone`
ne veut rien dire : « 650 00 00 01 » et « +237650000001 » sont le même numéro et
doivent entrer en collision. Deux règles séparées appliquent la forme
(`CameroonPhoneNumber`) et l'unicité (`UniquePhoneNumber`, qui compare la forme
normalisée).

**Hypothèse retenue — toujours non confirmée.** Neuf chiffres nationaux
commençant par **6** (mobile) ou **2** (fixe), avec préfixe `+237`, `237`,
`00237` ou aucun, espaces et tirets acceptés. **Je n'ai pas de source
vérifiable** pour cette règle ; elle est délibérément large, car un refus
injustifié coûte plus cher qu'une acceptation trop permissive. Elle tient dans
une expression régulière d'une seule classe : la resserrer est trivial.

**Piège évité, et testé.** Un fixe commence par 2, comme l'indicatif pays. Le
préfixe `237` n'est retiré que si la longueur restante est correcte, sinon
`237222222` (un numéro fixe national) serait lu comme `222222` — le test
`it('does not mistake a landline prefix for the country code')` verrouille ce cas.

---

### 2026-09-19 — Un shell, quatre navigations

**Décision.** Écart assumé au cahier des charges, qui demandait des « layouts
par rôle ». Le projet garde **un seul shell applicatif** et lui injecte une
navigation choisie par `Support\RoleNavigation` selon le rôle et l'état du
compte.

**Justification.** Quatre layouts complets auraient signifié corriger quatre
fois chaque détail d'ergonomie, alors que seule la navigation diffère
réellement. Le résultat visible pour l'utilisateur est identique.

**Note.** Un compte non actif reçoit la navigation `pending`, qui ne propose que
l'écran de statut : le menu ne montre pas des espaces auxquels l'accès est
refusé.

---

### 2026-09-19 — Interface entièrement en français, sans table de correspondance

**Décision.** Le texte français est écrit **directement** dans `__()`, et
`lang/fr.json` a été supprimé.

**Justification.** `lang/fr.json` associait des clés anglaises à des textes
français. Une fois toutes les vues traduites, plus aucune clé anglaise n'existe :
le fichier était devenu une table morte. Écrire le français dans `__()` a un
avantage concret : si un jour une chaîne échappe aux fichiers de langue, elle
s'affiche en français, pas en anglais. Les appels `__()` restent en place, donc
l'ajout d'une seconde langue reste possible.

Les fichiers `lang/fr/*.php` (validation, auth, passwords, pagination) sont
conservés : ce sont ceux que Laravel consulte pour ses propres messages.

---

### 2026-09-19 — Piège Livewire : composants pleine page et racine unique

Les composants Livewire pleine page (`RegisterFarmer`, `Account\Status`) doivent
rendre **un seul élément racine** ; le layout est appliqué automatiquement
(`livewire.component_layout`, par défaut `layouts::app`). Les envelopper dans
`<x-layouts::app>` provoque une `MultipleRootElementsDetectedException`.
`RegisterFarmer` déclare `#[Layout('layouts::auth')]` pour obtenir le layout
d'authentification.

Les vues d'authentification de Fortify ne sont **pas** des composants Livewire —
elles sont rendues par `view()` — et gardent donc leur `<x-layouts::auth>`.

---

### 2026-09-19 — Bug de traduction attrapé à la compilation des vues

La première passe de traduction a produit `:title="__("Se connecter")"` : des
guillemets doubles à l'intérieur d'un attribut HTML lui-même délimité par des
guillemets doubles, ce qui casse la compilation Blade. Toutes les chaînes ont
été repassées en apostrophes échappées, sûres dans les deux contextes. Les
tests de rendu ont signalé l'erreur immédiatement.
