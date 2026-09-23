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

---

## Phase 3 — Passerelle de paiement simulée

### 2026-09-19 — Le callback est une vraie requête HTTP, traitée par le noyau

**Décision.** Le job `DeliverSimulatedCallback` construit une `Request` et la
passe à `app()->handle()`. Routage, middleware, exemption CSRF, vérification de
signature et contrôleur s'exécutent réellement.

**Justification.** Trois options se présentaient :

| Option | Verdict |
|---|---|
| Requête interne via le noyau | **retenue** — fidèle, testable, sans réseau |
| Vrai HTTP via `Http::post()` | exigerait `php artisan serve` en plus de `queue:work`, et les tests devraient simuler le client HTTP — ce qui retirerait précisément ce qu'on veut vérifier |
| Appel direct du service | contournerait routage, middleware et signature : la RG06 ne serait plus réellement testée |

Passer à un vrai opérateur ne changerait que le **producteur** du callback ; son
traitement resterait identique.

---

### 2026-09-19 — L'idempotence est garantie par la base, pas par un `if`

**Décision.** Nouvelle table `payment_callbacks`, avec `event_id` **unique**.
Le traitement insère d'abord la ligne ; une collision signifie « déjà traité »,
et la réponse est 200 sans aucun effet.

**Justification.** `payments.idempotency_key` identifie **un paiement**, pas
**un callback**. Vérifier l'existence en PHP laisserait une fenêtre pendant
laquelle deux livraisons simultanées ne trouveraient rien et appliqueraient
toutes deux l'effet métier. La contrainte d'unicité, elle, ne perd pas cette
course.

**Écart assumé.** La phase 1 devait porter toutes les migrations ; celle-ci
arrive en phase 3, parce que le besoin n'apparaît qu'ici. Il valait mieux
l'ajouter au bon moment que de deviner sa forme deux phases trop tôt.

---

### 2026-09-19 — Le montant annoncé par le callback est comparé, jamais cru

**Décision.** Si le montant du callback diffère de celui enregistré, le
callback est journalisé, marqué traité, et **aucun effet n'est appliqué**.

**Justification.** Un callback est une information venue de l'extérieur. Le
montant dû est une donnée de la plateforme, lue dans `Setting` au moment de
l'initiation. Un écart signale un problème en amont : la réponse sûre est de ne
rien faire, pas de suivre l'appelant.

---

### 2026-09-19 — Décider d'expirer relève de l'application, pas de la passerelle

**Décision corrigée en cours de phase.** `getStatus()` rapporte ce que la
passerelle sait, sans jamais transformer une attente en expiration. C'est
`PaymentService::reconcile()` qui décide, avec la fenêtre que l'appelant lui
donne.

**Ce qui l'a révélé.** Un test de l'option `--minutes` de la commande de
réconciliation. L'option promettait de piloter la fenêtre d'attente, mais le
seuil réel venait de `config('payments.expiration_minutes')` : passer
`--minutes 1` n'expirait rien. Le réglage était décoratif.

**Pourquoi la nouvelle conception est meilleure.** Un vrai opérateur a son
propre délai et ne prendrait pas le nôtre en argument. Le contrat reste ainsi
crédible pour une implémentation réelle, et la politique d'expiration est au
seul endroit qui la connaît.

---

### 2026-09-19 — Un effet métier manquant lève une exception

**Décision.** `OutcomeRegistry` ne connaît que `registration_fee`. Les trois
autres usages (`order`, `training`, `subscription`) lèvent
`PaymentOutcomeNotHandled`.

**Justification.** Un `default => null` silencieux signifierait qu'un client
paie une commande et qu'il ne se passe rien — découvert bien plus tard, côté
client. Une exception rend l'oubli impossible à manquer au moment où la phase
correspondante commence. Le contrôleur journalise en erreur avant de la
relancer.

---

### 2026-09-19 — Le retour navigateur n'accorde rien

**Décision.** L'écran `payments.pending` interroge le paiement toutes les deux
secondes et **affiche** ce que le serveur a confirmé. Il n'a aucune méthode qui
modifie quoi que ce soit.

**Justification.** C'est littéralement la RG06. Un test dédié gèle la file
d'attente, clique « Confirmer », puis rafraîchit l'écran deux fois : le
paiement reste `initiated`. La page ne peut rien faire aboutir.

---

### 2026-09-19 — La page de paiement est volontairement voyante

Bandeau « Environnement de test » en bordure pointillée ambre, mention
explicite qu'aucun opérateur n'est contacté et qu'aucun argent ne circule. Une
page de paiement factice qui ressemble à une vraie est un piège : mieux vaut
qu'elle soit impossible à confondre.

Trois boutons couvrent les issues d'un paiement réel, dont les deux qu'un
opérateur rend difficiles à déclencher à la demande : **Refuser** et **Laisser
expirer**. Deux numéros imposent leur issue quel que soit le bouton
(`670 00 00 00` échoue, `670 00 00 99` n'aboutit jamais), afin de rejouer ces
cas depuis un script.

---

### 2026-09-19 — Bug attrapé par le script de vérification : les numéros de la factory

`UserFactory` numérotait les téléphones depuis un compteur statique démarrant à
zéro. Deux collisions s'en sont suivies :

1. avec les numéros fixes du `DemoSeeder` — la factory produisait
   `+237600000001`, déjà pris par le super-admin ;
2. avec elle-même, d'une exécution de script à l'autre : le compteur vit dans le
   processus, les numéros vivent dans la base.

**Correction.** Les numéros générés occupent une bande réservée `+23761…`,
qu'aucun compte de démonstration n'utilise, et le compteur est initialisé une
fois par processus depuis le plus grand numéro déjà stocké.

Le problème n'apparaissait pas dans la suite de tests, où la base est
réinitialisée : il fallait un script réel pour le faire sortir.

---

### 2026-09-19 — Le paiement exige la file d'attente, par conception

La confirmation transite par `php artisan queue:work`. Ce n'est pas une
contrainte subie : un vrai opérateur répond hors bande, quelques secondes plus
tard, sur sa propre connexion. Exécuter le callback en ligne masquerait tous les
défauts qui n'apparaissent que lorsque la réponse arrive après que le navigateur
est passé à autre chose.

C'est rappelé dans le `README.md`, dans `CLAUDE.md`, **sur la page de paiement
elle-même** et dans la sortie de la commande Artisan.

---

## Phase 4 — Administration

### 2026-09-19 — Le rôle ouvre la porte, le privilège autorise l'action

**Décision.** Le middleware `role:admin` donne accès à `/admin`. **Il n'autorise
rien à l'intérieur.** Chaque action passe par une Policy adossée à un privilège,
et un Gate est déclaré par privilège depuis `Privilege::catalogue()`.

**Justification.** Déclarer les Gates depuis le catalogue plutôt qu'un par un
garde le seeder, l'écran des privilèges et les Policies sur une seule liste :
un nouveau privilège ne peut pas exister à un endroit et être oublié à un autre.

**Vérifié dans l'application réelle**, pas seulement en tests : un administrateur
ne disposant que de `farmers.approve` reçoit 403 sur les paramètres, le journal
d'audit et les privilèges, et sa navigation ne lui propose que ce qu'il peut
faire.

---

### 2026-09-19 — Anonymiser, c'est supprimer la donnée

**Décision.** `users.email` et `users.phone` deviennent nullables, et une
suppression logique les met à `NULL`.

**Justification.** Trois options existaient :

| Option | Verdict |
|---|---|
| **`NULL`** | **retenue** — anonymiser, c'est retirer la donnée. MySQL accepte plusieurs `NULL` sous un index unique, donc l'unicité tient toujours pour les comptes vivants. |
| `supprime-{id}@agritech.invalid` | garde une donnée fictive qui ressemble à une vraie, et pollue les recherches |
| Chaîne non téléphonique dans `phone` | stocke une valeur qui viole le format de la colonne |

**Effet de bord voulu.** Une adresse et un numéro libérés redeviennent
disponibles pour une nouvelle inscription — un test le vérifie.

`first_name` / `last_name` deviennent « Compte » / « supprimé » : ils restent
non nullables, et un historique de commande doit bien afficher quelque chose.

**Troisième migration hors phase 1**, pour la même raison que les précédentes :
la forme exacte du besoin n'apparaît qu'ici.

---

### 2026-09-19 — Le journal d'audit n'enregistre pas ce qu'il vient d'effacer

**Décision.** L'entrée d'audit d'une suppression porte `had_email: true → false`,
jamais l'adresse elle-même.

**Justification.** Écrire l'adresse dans le journal annulerait l'anonymisation
que ce journal est en train d'enregistrer. L'identifiant du compte et son rôle
suffisent à suivre l'historique. Un test vérifie explicitement que l'adresse
n'apparaît nulle part dans l'entrée.

---

### 2026-09-19 — Journal écrit à la main, pas par observateur de modèle

**Décision.** `AuditLogger::record()` est appelé explicitement par chaque
service.

**Justification.** Un observateur tracerait tout, y compris les écritures
techniques — un `updated_at` touché par un job en file d'attente — et noierait
les actions administratives dans le bruit. La RG11 parle d'« actions
administratives sensibles » : les nommer une par une est plus juste, et bien
plus lisible à la relecture six mois plus tard.

**Corollaire :** une modification qui ne modifie rien n'écrit rien. Enregistrer
des changements vides produirait un journal que personne ne lit.

---

### 2026-09-19 — Deux garde-fous contre le verrouillage hors de la plateforme

**Décision.** Un administrateur ne peut ni se suspendre, ni se supprimer, ni
modifier ses propres privilèges. Et le dernier administrateur actif ne peut pas
être supprimé.

**Ce que le test a révélé.** Le second garde-fou est **inatteignable depuis
l'interface** : l'acteur est forcément un administrateur actif, donc en
l'excluant il en reste toujours au moins un. Mon premier test prétendait le
contraire et échouait à juste titre.

Plutôt que de supprimer ce code ou d'écrire un test qui ne prouve rien, je l'ai
gardé — il protège les appels qui ne viennent pas de l'écran, une commande
console ou un seeder — et je le teste **au niveau de la Policy**, là où la
situation est réellement atteignable. Le commentaire du test dit exactement
pourquoi.

---

### 2026-09-19 — Incohérence trouvée dans l'application réelle

L'écran des privilèges s'ouvrait à tout administrateur (`viewAny` = être admin),
alors que la navigation le cachait à ceux qui n'ont pas `privileges.manage`. Le
menu et l'URL directe ne disaient pas la même chose.

**Corrigé :** l'écran est désormais fermé sur le privilège lui-même. Qui détient
quels pouvoirs est précisément ce que la RG07 est là pour garder fermé.

Ce décalage ne se voyait pas dans les tests, qui appelaient les actions. Il a
fallu ouvrir les six URL avec un administrateur volontairement limité pour le
voir.

---

### 2026-09-19 — Ce que la phase 4 ne livre pas

Le privilège `publications.moderate` existe et sa vérification est en place,
**mais il n'y a pas d'écran de modération** : les produits et les formations
n'ont pas encore d'écran de publication, c'est la phase 5. Le tableau de bord
affiche le compteur des publications en attente avec la mention « écran de
modération à venir ».

Livrer un écran vide aurait été pire que de l'annoncer.

---

## Phase 5 — Catalogue et publication

### 2026-09-19 — Les images passent par un contrôleur, pas par `storage:link`

**Décision.** Les images de produits sont stockées sur un **disque privé** et
servies par `ProductImageController`, jamais par une URL de fichier directe.

**Justification.** La méthode habituelle — disque `public` + `php artisan
storage:link` — dépend d'un lien symbolique dont le comportement sous Windows
**n'a pas pu être vérifié** depuis cet environnement. S'il échoue, aucune image
ne s'affiche, et le symptôme (« mes photos ne montent pas ») ne désigne pas sa
cause. Le contrôleur supprime ce risque entièrement.

Deux bénéfices annexes : c'est déjà l'architecture qu'exigera la RG05 pour les
contenus de formation payants, et les en-têtes de cache sont maîtrisés
(`immutable`, un an — les noms de fichiers sont des ULID, donc jamais réécrits).

**Coût assumé.** Une requête PHP par image au lieu d'un fichier statique.
Négligeable pour un usage local ; à reconsidérer si le projet devait un jour
servir du trafic réel.

---

### 2026-09-19 — Un produit d'agriculteur suspendu disparaît du catalogue

**Décision.** Le scope `visibleToPublic()` filtre sur **deux** conditions : le
produit est `published` **et** son agriculteur est `active`.

**Justification.** Sans cela, suspendre un compte le laisserait vendre — la
sanction ne servirait à rien. C'est la visibilité publique qui dépend du
vendeur, pas seulement de la fiche. Trois tests le vérifient : suspension,
suppression logique, et accès direct à l'URL de la fiche, qui répond **404** et
non 403 — inutile de confirmer à un visiteur que la fiche existe.

---

### 2026-09-19 — Les noms de fichiers téléversés sont jetés

**Décision.** Chaque image reçoit un nom ULID. Le nom d'origine n'est conservé
nulle part.

**Justification.** Un nom de fichier choisi par celui qui téléverse est une
**instruction adressée au système de fichiers**, pas une information utile.
Le type MIME **réel** est vérifié (règle `image` de Laravel, qui lit le
contenu), pas l'extension du nom — un test envoie du code PHP nommé `photo.jpg`
et vérifie qu'il est refusé.

Limites également appliquées : taille (4 Mo), dimensions (4000 x 4000) et
nombre d'images par produit (5).

---

### 2026-09-19 — Le slug suit le nom, mais seulement s'il change

**Décision.** `SlugGenerator` ajoute un suffixe numérique jusqu'à trouver un
slug libre. À la modification, le slug **ne bouge pas** si le nom n'a pas
changé.

**Justification.** Deux agriculteurs vendant « Tomate fraîche » est le cas
normal, pas le cas limite, et `products.slug` est unique. Sans générateur, la
seconde fiche échouerait. Et une URL stable vaut mieux qu'une URL jolie : un
lien déjà partagé ne doit pas casser parce qu'une description a été retouchée.

Même raisonnement pour les catégories : **renommer une catégorie ne change pas
son slug**, qui est déjà dans des URL de catalogue.

---

### 2026-09-19 — La modération couvre produits et formations dès maintenant

**Décision.** L'écran de modération traite les deux, alors que les formations
n'auront leur écran de publication qu'en phase 7.

**Justification.** Leur machinerie de statut est identique et existe depuis la
phase 1. Attendre aurait imposé soit un écran à moitié vide maintenant, soit
deux écrans à fusionner plus tard. Cela solde aussi la dette annoncée en phase
4 : le compteur du tableau de bord pointe désormais vers un écran réel.

---

### 2026-09-19 — Une signature qui mentait, corrigée par Larastan

`PublicationService` déclarait accepter un `Model` tout en appelant
`publish()`, `rejectPublication()`, `$status` et `$farmer`. Le docblock disait
`Product|Training` ; la signature, elle, acceptait n'importe quel modèle.

**Corrigé par un type union `Product|Training`** plutôt que par une interface
inventée pour la circonstance. Les deux modèles partagent réellement cette
machinerie ; le dire est plus honnête qu'ajouter une abstraction dont le seul
rôle serait de faire tenir une signature.

Larastan a aussi trouvé une dépendance injectée dans `ProductService` puis
jamais utilisée. Retirée.

---

### 2026-09-19 — Les images de démonstration sont générées, pas commitées

**Décision.** `DemoSeeder` dessine une image par produit avec GD.

**Justification.** Un catalogue de rectangles gris donne l'impression d'une
application cassée. Mais committer des photos poserait deux problèmes : le poids
du dépôt, et la licence — aucune photo ne peut être versionnée sans savoir d'où
elle vient. GD est déjà une extension requise ; générer à l'installation règle
les deux.

---

### 2026-09-19 — Page d'accueil remplacée

Le gabarit de bienvenue de Laravel a été remplacé par une vraie page AgriTech
qui mène au catalogue, avec un layout `public` distinct (en-tête simple, pas de
barre latérale) pour les visiteurs non connectés.

---

### 2026-09-19 — Le panier vit en base, et seulement pour un client connecté

**Décision.** Tables `carts` et `cart_items`, un panier par client. Un visiteur
qui clique « Ajouter au panier » est envoyé se connecter, puis ramené sur la
fiche du produit.

**Justification.** L'usage visé est majoritairement mobile : un panier en
session disparaît au premier rechargement, au premier changement de réseau, au
premier retour depuis une autre application. C'est exactement le moment où l'on
perd la vente.

**Alternative écartée.** Panier en session fusionné à la connexion. Cela ajoute
un chemin de fusion — quantités cumulées, produits devenus indisponibles entre
temps, conflits avec un panier déjà existant — à écrire et à tester, pour un
gain limité à un visiteur qui n'a pas encore de compte et qui devra en créer un
avant de payer de toute façon.

---

### 2026-09-19 — Une ligne indisponible bloque la commande, elle ne disparaît pas

**Décision.** Une ligne dont le produit a été dépublié, dont l'agriculteur a été
suspendu ou dont le stock est descendu sous la quantité demandée est **affichée,
signalée, exclue du total**, et empêche de commander tant qu'elle est là.

**Justification.** Retirer la ligne en silence change le montant sous les yeux
du client sans rien expliquer. Et commander « ce qui reste » suppose que la
plateforme décide à sa place de ce qu'il accepte d'acheter.

---

### 2026-09-19 — Stock disparu à la confirmation : annulation totale et remboursement

**Décision.** Si, au moment où le paiement est confirmé, un produit de la
commande n'est plus disponible en quantité suffisante, la commande est
**annulée en entier** et le paiement passe en `refunded` via la passerelle. Le
client est notifié avec le nom du produit en cause. Même traitement pour un
paiement qui arrive après l'annulation automatique de sa commande.

**Justification.** C'est la conséquence directe de RG04 : le stock n'étant pas
réservé à la commande, deux clients peuvent légitimement commander le même lot,
et le second doit être traité proprement.

**Alternative écartée.** Livrer partiellement ce qui reste. Cela obligerait à
inventer un remboursement au prorata, à recalculer des sous-totaux et des
commissions après coup, et à décider unilatéralement que le client veut trois
sacs alors qu'il en a commandé dix. Rien de tout cela n'est demandé.

---

### 2026-09-19 — Un taux de commission absent lève une exception

**Décision.** `OrderService::commissionRate()` ne retourne pas `0` quand le
paramètre manque : il lève une `DomainException`.

**Justification.** Une valeur par défaut à zéro offrirait chaque commande
commission comprise, silencieusement et pour toujours. Le délai d'annulation,
lui, garde un repli à 30 minutes : ce n'est pas de l'argent.

---

### 2026-09-19 — Les produits sont verrouillés triés par identifiant

**Décision.** `OrderService` et `OrderFulfilmentService` lisent les produits
avec `->orderBy('id')->lockForUpdate()`.

**Justification.** Deux paniers contenant les deux mêmes produits dans l'ordre
inverse prendraient les verrous en sens contraire et pourraient s'interbloquer.
Un ordre de verrouillage commun supprime la possibilité même du cycle.

---

### 2026-09-19 — Une suite de tests dédiée à la concurrence

**Décision.** Un répertoire `tests/Concurrency`, une entrée dans `phpunit.xml`,
et `DatabaseTruncation` au lieu de `RefreshDatabase` pour cette suite. Les tests
lancent deux **vrais sous-processus** qui attendent un instant convenu avant de
délivrer chacun leur callback.

**Justification.** `RefreshDatabase` enveloppe le test dans une transaction : un
second processus ne verrait aucune des données créées. Et un seul processus PHP
sérialise les deux confirmations par construction — un tel test passerait aussi
bien sans verrou, donc ne prouverait rien.

**Vérifié.** En retirant `lockForUpdate` de `OrderFulfilmentService`, les deux
tests de la suite échouent : deux commandes payées pour un seul lot, et un
décrément perdu. Le test discrimine réellement.

---

### 2026-09-19 — Fixtures de test partagées dans `tests/Helpers.php`

**Décision.** Les helpers utilisés par plusieurs fichiers de test
(`productOnSale()`, `orderFor()`, `startOrderPayment()`…) sont dans
`tests/Helpers.php`, autoloadé par `composer.json` (`autoload-dev.files`).

**Justification.** Déclarer une fonction dans un fichier de test et l'appeler
depuis un autre fonctionne tant que Pest charge les deux — c'est-à-dire tant que
personne ne renomme ni ne supprime le premier. Une dépendance implicite à
l'ordre de chargement n'est pas une dépendance qu'on veut découvrir six mois
plus tard.

---

### 2026-09-19 — Un agriculteur ne voit pas les sous-commandes annulées

**Décision.** `/agriculteur/commandes` liste uniquement les statuts `paid`,
`preparing` et `delivered`.

**Justification.** Rien n'annule une sous-commande déjà payée : une
sous-commande annulée l'a donc toujours été **avant** paiement. Elle n'a jamais
représenté du travail pour l'agriculteur, et l'afficher ne ferait qu'encombrer
l'écran de commandes qui n'ont jamais existé pour lui.

---

## Phases 7 à 11 — Formations, abonnements, messagerie, dashboards, finalisation

### 2026-09-20 — L'accès au contenu d'une formation est un contrôleur, pas une URL

**Décision.** Les fichiers de formation (MP4/WebM/PDF) vivent sur le disque
privé et sont servis par `TrainingContentController`, qui vérifie l'entitlement
(RG05) avant d'envoyer le premier octet. Le chemin est masqué à la
sérialisation (`#[Hidden]` sur `TrainingContent`), et la fiche publique ne
montre que les titres des modules.

**Justification.** Même architecture que les images produits, mais avec un
droit à vérifier : le contenu d'une formation est payant, une URL directe
en ferait une ressource publique dès que quelqu'un la partage.

**Alternative écartée.** URLs signées à expiration : introduit une horloge
supplémentaire et une dépendance au lien de signature, pour un bénéfice nul en
local où le contrôleur suffit.

---

### 2026-09-20 — Un achat de formation est unique par contrainte de base

**Décision.** La table `training_purchases` porte une contrainte d'unicité sur
`(client_id, training_id)`, et `TrainingAccessService::recordPurchase()` rend
la ligne existante plutôt que d'en créer une seconde.

**Justification.** Le callback d'un paiement rejoué, ou deux paiements
simultanés sur la même formation, ne doivent jamais produire deux achats. La
base tranche, pas un `if`. C'est le même raisonnement que
`payment_callbacks.event_id`.

---

### 2026-09-20 — Pas de chevauchement d'abonnements

**Décision.** `SubscriptionService::start()` refuse toute nouvelle souscription
tant qu'un terme est en cours. Changer de plan se fait une fois le terme passé.

**Justification.** Gérer le chevauchement obligerait à inventer un remboursement
au prorata ou une extension automatique : deux comportements que personne
n'a demandés, et le prorata contredit la règle qui a fait écarter la livraison
partielle (pas de remboursement au prorata inventé). Le refus est honnête, le
message explique quoi faire.

**Conséquence assumée.** Un client qui veut passer à un plan supérieur attend
la fin de son terme. Pour un prototype local, la simplicité vérifiable vaut
mieux qu'une proration speculative.

---

### 2026-09-20 — Le registre des effets métier est un `match` exhaustif

**Décision.** `OutcomeRegistry::for()` est devenu un `match` sur
`PaymentPurpose`, sans tableau ni `??` ni `isset()`.

**Justification.** Les quatre purposes existent désormais toutes. Un `match`
exhaustif fait de « ajouter un purpose sans son handler » une `TypeError` au
premier passage, plutôt qu'une exception à l'exécution — le compilateur
statique vérifie l'exhaustivité. C'est la fin de la trajectoire voulue en
phase 3 : le registre n'a jamais eu vocation à rester incomplet silencieusement.

---

### 2026-09-20 — Messagerie par polling, pas par WebSocket

**Décision.** Les fils de discussion se rafraîchissent par `wire:poll.5s`.

**Justification.** La contrainte « 100 % local, sans service externe » écarte
Reverb et tout serveur WebSocket dédié, comme décidé en phase 0. Le polling
Livewire couvre le besoin de démonstration sans infrastructure supplémentaire.

---

### 2026-09-20 — La sous-commande annulée n'entre dans aucun total

**Décision.** Le tableau de bord agriculteur ne somme que les sous-commandes
`paid`, `preparing` et `delivered`.

**Justification.** Cohérence avec l'écran des commandes : une sous-commande
annulée a toujours été annulée avant paiement, elle n'a jamais été du travail.
Un chiffre d'affaires qui la compterait mentirait à son premier regard.

---

### 2026-09-20 — Design system Stitch appliqué au thème global

**Décision.** Les tokens du design system livré dans
`stitch_conception_design_application/agritech_design_system/DESIGN.md` sont
portés dans `resources/css/app.css` (palette, polices, rayons, ombres) et
`vite.config.js` (Plus Jakarta Sans pour les titres, Inter pour le corps).

**Justification.** Le design existait dans le dépôt sans être branché ; le
reprendre tel quel aligne l'interface sur la conception validée au lieu
d'improviser une palette. Les neutres ont été mappés sur l'échelle `zinc` de
Tailwind pour teinter l'ensemble des composants Flux existants sans les
réécrire, l'accent devient le vert forêt `#1B6B3A`, et le mode sombre utilise
le vert clair `#9AE9AB` prévu par le design system.

**Limites assumées.** Les composants Flux imposent leur structure ; la palette
et la typographie sont appliquées globalement, pas écran par écran. Les cartes
« méthode de paiement » teintées MTN/Orange du design system restent à faire
écran par écran si elles deviennent souhaitables.

---

### 2026-09-20 — La suite de tests rendue insensible à l'environnement du shell

**Décision.** `phpunit.xml` force ses variables (`force="true"`) et
`Tests\TestCase` promeut `$_ENV` vers `$_SERVER` avant le démarrage de
l'application.

**Justification.** Sur un poste où le shell exporte déjà `APP_ENV`, `DB_DATABASE`,
`SESSION_DRIVER`…, phpdotenv lisait `$_SERVER` en priorité et les valeurs du
shell écrasaient celles de la suite : les POST répondaient 419 (CSRF actif hors
« testing »), la file ne tournait plus en `sync`, et 37 tests échouaient sans
rien dire sur le code. `force="true"` couvre le cas `getenv()`/`putenv()`, la
promotion couvre la lecture `$_SERVER` de phpdotenv v5. Le correctif est
multiplateforme — le développeur Windows n'a pas `env -u`.

**Vérifié.** Les 37 échecs disparaissent avec le seul changement de ces deux
fichiers ; aucun code applicatif n'était en cause.

---

### 2026-09-22 — Les photographies de démonstration sont téléchargées puis commitées

**Décision.** Le jeu de démonstration s'appuie désormais sur **18 photographies
réelles** (13 pour les produits, 4 pour les formations, 1 pour l'accueil),
rangées dans `database/seeders/photos` et `public/images`. Le dessin GD reste
en **repli** : si un fichier manque, ou sur une machine sans l'extension GD,
l'amorçage retombe sur les illustrations générées.

**Ce que cela révise.** Une décision antérieure écartait les photos commitées
pour deux raisons : le poids du dépôt, et l'impossibilité de verser une image
sans connaître sa licence. La demande a changé — il fallait étoffer le design —
et les deux objections se traitent :

- **Licence.** Les fichiers viennent de dépôts libres, trouvés via
  [Openverse](https://openverse.org), et sont limités à **CC0 1.0**,
  **PDM 1.0** (domaine public) et **CC BY 2.0**. Aucune licence à clause de
  partage à l'identique, aucune licence non commerciale. Le titre, l'auteur, la
  licence et l'URL source de chaque fichier sont enregistrés dans
  `database/seeders/photos/credits.json` et repris dans `CREDITS.md`.
- **Poids.** Recadrées au rapport d'affichage, redimensionnées (900 px pour les
  produits, 1 000 px pour les formations, 1 400 px pour l'accueil) et
  enregistrées en JPEG progressif à qualité 80 : **2,9 Mo au total**.

**Le téléchargement a lieu une fois, pas à l'amorçage.** Les fichiers sont dans
le dépôt ; `php artisan migrate:fresh --seed` n'appelle aucun service distant.
La contrainte « l'application tourne sans connexion Internet » tient toujours,
amorçage compris.

**Obligation d'attribution honorée.** Les fichiers sous CC BY exigent de citer
l'auteur, la licence et la source. La page `/credits-photos`, liée depuis le
pied de page public, le fait pour chacun d'eux. Ce n'est pas une politesse :
sans cette page, l'usage de ces images ne serait pas conforme.

**Alternative écartée.** Les URL distantes des maquettes
(`lh3.googleusercontent.com`). Elles rendraient le catalogue dépendant d'un
service tiers, donc inutilisable hors ligne, et ces URL expirent.

---

### 2026-09-22 — La couverture de formation accepte le JPEG comme le PNG

**Décision.** `Training::coverPath()` cherche `training-covers/{slug}.jpg` puis
`{slug}.png`, et le contrôleur sert ce que la méthode trouve.

**Justification.** Une photographie se stocke en JPEG, un dessin GD en PNG. Le
fichier reste indexé par le slug — il n'y a toujours pas de colonne de
couverture à tenir à jour — et les deux sources coexistent sans que l'écran ait
à savoir laquelle est là.

Au passage : une photographie l'emporte sur un dessin laissé par un amorçage
précédent. `migrate:fresh` vide la base, pas le disque ; sans cela, une machine
déjà amorcée aurait gardé ses illustrations pour toujours.

---

### 2026-09-22 — La légende des illustrations GD est repliée en ASCII

**Symptôme.** Sur les images générées, « Régime de plantain » s'affichait
`RÃ‰GIME DE PLANTAIN`, et le texte était décentré d'un cran par accent.

**Cause.** `imagestring()` et les polices bitmap intégrées à GD travaillent
**octet par octet**. Un « É » en UTF-8 occupe deux octets et se dessine donc
comme deux glyphes faux ; et la largeur, mesurée en caractères avec
`mb_strlen()`, ne correspondait plus à l'avance réelle, en octets.

**Décision.** La légende est repliée en ASCII (`É` → `E`, `œ` → `oe`, les
apostrophes typographiques → `'`), puis mesurée avec `strlen()`. Les polices
intégrées de GD n'ont de toute façon aucun glyphe accentué à proposer :
retirer l'accent est plus honnête que le rendre faux.

**Portée.** Ces illustrations ne sont que le repli des photographies. Le
symptôme n'apparaît que sur une machine sans photographies amorcées, ou sans
l'extension GD — mais il n'avait aucune raison de rester.

---

### 2026-09-23 — Les formations de démonstration livrent des modules PDF générés

**Décision.** `DemoSeeder` écrit désormais les modules de chaque formation de
démonstration sur le disque privé, sous forme de PDF construits par
`Support\PlaceholderPdf`, et le format des quatre formations passe à
**Document PDF**.

**Justification.** Sans modules, trois écrans du lot 3 n'ont rien à montrer :
le programme de la fiche est vide, le lecteur n'ouvre rien, et la règle RG05
— le fichier n'est servi qu'à qui y a droit — n'est jamais exercée par la
démonstration. Or une vidéo ne peut pas être fabriquée ici : aucun encodeur
n'est une dépendance du projet, et en ajouter un casserait la promesse
d'installation 100 % locale, sous Windows comme ailleurs. Le PDF est le seul
format de document qu'on sait produire en PHP pur, sans extension, sans
binaire et sans réseau.

Le format annonce ce que l'acheteur recevra. Laisser « Vidéo » sur une
formation qui ne contient que des documents serait exactement la promesse que
ce projet s'interdit d'afficher ; les quatre formations disent donc ce
qu'elles contiennent vraiment.

**Conséquence assumée.** Les pastilles « Vidéo » du catalogue tombent à zéro.
L'écran n'affiche plus une pastille de format que personne ne vend : un filtre
qui ne peut rien renvoyer n'est pas un filtre, c'est un cul-de-sac. Elle
reparaît dès qu'un agriculteur téléverse une vidéo depuis son espace, ce que
le formulaire accepte toujours.

**Alternative écartée.** Télécharger une vidéo libre de droits et la committer.
Elle pèserait plusieurs centaines de kilo-octets pour un contenu qui n'apprend
rien, et ferait dépendre l'amorçage d'un fichier binaire de plus.

---

### 2026-09-23 — Le lecteur de formation n'affiche aucune progression

**Décision.** L'écran `agritech_lecteur_de_formation` est reproduit sans sa
jauge d'avancement (« 67 % · 8/12 validés »), sans le téléchargement
hors-ligne et sans l'onglet « Notes & Discussion ». Les commandes de lecture
dessinées dans la maquette — vitesse, sous-titres, plein écran, retour de dix
secondes — laissent la place aux commandes natives du navigateur.

**Justification.** Aucune table ne retient où un client s'est arrêté, ni ce
qu'il a annoté. Afficher « 8 modules validés » demanderait d'inventer la
donnée à l'affichage, et un pourcentage faux est pire qu'un pourcentage
absent. Quant aux commandes de lecture, `<video controls>` les fournit déjà,
correctement, y compris au clavier et aux lecteurs d'écran.

**Ce qui reste.** La position du module dans la série (« Module 2 sur 5 »),
qui se lit dans `training_contents.position`, la navigation précédent /
suivant, et la liste des modules avec celui qui est ouvert.

**Si la progression devait exister.** Il faudrait une table
`training_progress (client_id, content_id, completed_at)` écrite par un
service, et l'écran la lirait comme il lit tout le reste.
