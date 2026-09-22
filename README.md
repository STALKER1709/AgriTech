# AgriTech

Plateforme web qui met en relation **agriculteurs** et **clients** au Cameroun :
vente de produits agricoles et de formations, abonnements, messagerie, et
paiements Mobile Money **simulés localement**.

> **Application 100 % locale.** Aucun déploiement en ligne, aucun service
> externe, aucun compte tiers. Une fois les dépendances installées,
> l'application fonctionne **sans connexion Internet**.

---

## 0. Démarrage rapide — la liste complète

**Tout ce qu'il faut faire, dans l'ordre, pour lancer AgriTech sur une machine
Windows qui n'a jamais vu le projet.** Chaque étape renvoie à la section
détaillée correspondante si quelque chose coince.

Ouvrez le **terminal de Laragon** (bouton *Terminal*) ou PowerShell. Les
commandes sont identiques.

### ✅ Étape 1 — Vérifier les outils

```powershell
php -v          # doit afficher 8.4.x
composer -V     # doit afficher 2.8 ou plus
node -v         # doit afficher 20.x ou 22.x
git --version
```

> **Si `php -v` affiche 8.3 ou moins**, changez de version dans Laragon :
> clic droit sur l'icône → **PHP** → **Version** → choisissez 8.4, puis
> **redémarrez Laragon**. PHP 8.4 n'est pas négociable : Pest 5, le framework
> de test du projet, l'exige. Voir le § 1.

Vérifiez ensuite les extensions PHP :

```powershell
php -m
```

Doivent apparaître : `pdo_mysql`, `mbstring`, `openssl`, `tokenizer`, `xml`,
`ctype`, `json`, `fileinfo`, `gd`, `zip`, `intl`. Elles sont actives par défaut
dans Laragon. Voir le § 1 si l'une manque.

### ✅ Étape 2 — Démarrer MySQL

Dans Laragon, cliquez sur **Démarrer tout** et vérifiez que **MySQL** est vert.
Rien ne fonctionnera sans lui.

### ✅ Étape 3 — Récupérer le code

```powershell
cd C:\laragon\www
git clone https://github.com/STALKER1709/AgriTech.git
cd AgriTech
git checkout claude/new-session-wbw8iu
```

### ✅ Étape 4 — Créer les deux bases de données

```powershell
mysql -u root -e "CREATE DATABASE agritech CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root -e "CREATE DATABASE agritech_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

Il en faut bien **deux** : `agritech` pour l'application, `agritech_test` pour
les tests. Voir le § 2.2.

### ✅ Étape 5 — Installer les dépendances

```powershell
composer install
npm install
npm run build
```

Comptez quelques minutes la première fois. Une connexion Internet est
nécessaire **à cette étape uniquement** : ensuite, l'application tourne hors
ligne.

`npm run build` compile les styles et les scripts une fois pour toutes. Sans
lui, l'application afficherait `Vite manifest not found`.

### ✅ Étape 6 — Configurer l'environnement

```powershell
copy .env.example .env
php artisan key:generate
```

Ouvrez `.env` et vérifiez la section base de données. Les valeurs par défaut
correspondent à Laragon (`root`, sans mot de passe) :

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=agritech
DB_USERNAME=root
DB_PASSWORD=
```

Changez aussi la clé de signature des paiements simulés — n'importe quelle
chaîne fait l'affaire, elle ne sort jamais de votre machine :

```dotenv
PAYMENT_WEBHOOK_SECRET=une-chaine-aleatoire-de-votre-choix
```

### ✅ Étape 7 — Préparer la base et les fichiers

```powershell
php artisan migrate --seed
php artisan storage:link
```

> **Si `storage:link` échoue** (`symlink(): Protocol error` ou
> `Cannot create symlink`), c'est une restriction Windows sur les liens
> symboliques. Deux solutions, l'une ou l'autre :
> 1. activez le **Mode développeur** : *Paramètres → Système → Espace développeur* ;
> 2. **ou** relancez le terminal **en tant qu'administrateur** et réexécutez la commande.
>
> **Honnêteté :** cette commande n'a **pas pu être testée sous Windows** lors du
> développement — le projet a été écrit sur une machine Linux. Si le
> comportement diffère de ce qui est décrit ici, signalez-le.

### ✅ Étape 8 — Vérifier que tout est sain

```powershell
composer test
```

Cette commande enchaîne le formatage (Pint), l'analyse statique (Larastan) et
la suite de tests. **Les trois doivent passer.** Si un test échoue à ce stade,
inutile de lancer l'application : quelque chose ne va pas dans l'installation.

### ✅ Étape 9 — Lancer l'application : quatre terminaux

Chaque commande occupe son terminal. Gardez-les tous ouverts.

| Terminal | Commande | Rôle |
|---|---|---|
| **1** | `php artisan serve` | le serveur web |
| **2** | `npm run dev` | recompilation à chaque modification — *facultatif si vous ne modifiez pas le code* |
| **3** | `php artisan queue:work` | **la file d'attente** |
| **4** | `php artisan schedule:work` | les tâches planifiées |

> ### ⚠️ Le terminal 3 n'est pas optionnel
>
> Les confirmations de paiement passent par la file d'attente, exactement comme
> la réponse d'un vrai opérateur Mobile Money : hors bande, quelques secondes
> plus tard. **Sans `queue:work`, aucun paiement n'aboutira jamais** — l'écran
> d'attente tournera indéfiniment et vous croirez à un bug.
>
> Le terminal 4 sert à l'expiration automatique des paiements sans réponse. Vous
> pouvez vous en passer pour une simple démonstration, mais pas pour tester ce
> cas-là.

Ouvrez ensuite **http://localhost:8000**.

### ✅ Étape 10 — Se connecter

Tous les comptes de démonstration utilisent le mot de passe **`password`**.

| Pour voir… | Connectez-vous avec |
|---|---|
| L'administration complète | `admin@agritech.local` |
| Un espace client | `client@agritech.local` |
| Un espace agriculteur | `agriculteur@agritech.local` |
| **Le paiement des frais d'inscription** | `agriculteur-impaye@agritech.local` |
| Un compte en attente de validation | `agriculteur-attente@agritech.local` |

Le champ de connexion accepte aussi le **numéro de téléphone** — voir le § 4.

---

### Remettre la base à zéro

À tout moment, pour repartir du jeu de démonstration propre :

```powershell
php artisan migrate:fresh --seed
```

### Ce qu'il faut faire après un `git pull`

```powershell
composer install
npm install
php artisan migrate
npm run build
```

### Récapitulatif des pièges connus

| Symptôme | Cause | Solution |
|---|---|---|
| `composer install` réclame PHP 8.4 | Mauvaise version PHP active | Laragon → PHP → Version → 8.4, puis redémarrer |
| `Unknown database 'agritech'` | Bases non créées | Étape 4 |
| `Connection refused` sur le port 3306 | MySQL arrêté | Étape 2 |
| `Vite manifest not found` | Assets non compilés | `npm run build` (étape 5) |
| Un paiement reste bloqué sur « vérification » | `queue:work` ne tourne pas | Terminal 3 |
| `Cannot create symlink` | Droits Windows | Étape 7 |
| `Access denied ... agritech_test_test_1` | L'utilisateur MySQL ne peut pas créer de bases | Utiliser `root`, ou accorder les droits sur `agritech_test%` |
| Page blanche après une modification | Cache de vues | `php artisan view:clear` |

---

## 1. Prérequis (Windows)

| Outil | Version minimale | Vérification |
|---|---|---|
| **PHP** | **8.4** | `php -v` |
| **Composer** | 2.8 | `composer -V` |
| **Node.js** | 20 LTS ou 22 LTS | `node -v` |
| **MySQL / MariaDB** | MySQL 8 ou MariaDB 10.6+ | `mysql --version` |
| **Git** | récent | `git --version` |

**Laragon** fournit PHP, MySQL/MariaDB et un terminal en une seule installation.
XAMPP convient également.

> **PHP 8.4 est obligatoire**, et non 8.3. Laravel 13 accepte PHP 8.3, mais
> Pest 5 — le framework de test du projet — exige 8.4. Dans Laragon :
> *Menu → PHP → Version* pour sélectionner PHP 8.4.

### Extensions PHP requises

`pdo_mysql`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `json`,
`fileinfo`, `gd`, `zip`, `intl`.

> `bcmath` n'est **pas** nécessaire : les montants et les quantités sont
> manipulés en entiers par les classes `Money` et `Quantity`, sans arithmétique
> à précision arbitraire.

Vérification :

```powershell
php -m
```

Ces extensions sont activées par défaut dans Laragon. Avec XAMPP, décommentez au
besoin les lignes correspondantes dans `php.ini`.

---

## 2. Installation pas à pas

### 2.1 Récupérer le code

```powershell
git clone https://github.com/STALKER1709/AgriTech.git
cd AgriTech
```

### 2.2 Créer les deux bases de données

Le projet utilise **deux** bases : `agritech` pour l'application, `agritech_test`
pour la suite de tests.

Depuis le terminal Laragon :

```powershell
mysql -u root -e "CREATE DATABASE agritech CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root -e "CREATE DATABASE agritech_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

> Les tests exécutés en parallèle (`--parallel`) créent des bases temporaires
> `agritech_test_test_1`, `agritech_test_test_2`… L'utilisateur MySQL doit donc
> pouvoir créer et supprimer des bases. L'utilisateur `root` de Laragon en est
> capable par défaut.

### 2.3 Installer les dépendances

```powershell
composer install
npm install
```

### 2.4 Configurer l'environnement

```powershell
copy .env.example .env
php artisan key:generate
```

Ouvrez `.env` et ajustez si nécessaire la section base de données. Les valeurs
par défaut correspondent à Laragon (`root`, sans mot de passe) :

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=agritech
DB_USERNAME=root
DB_PASSWORD=
```

Renseignez également une clé de signature pour la passerelle de paiement
simulée :

```dotenv
PAYMENT_WEBHOOK_SECRET=une-chaine-aleatoire-de-votre-choix
```

### 2.5 Préparer la base et les fichiers

```powershell
php artisan migrate --seed
php artisan storage:link
```

> **`storage:link` sous Windows.** Cette commande crée un lien symbolique, ce qui
> demande des droits particuliers. En cas d'erreur `symlink(): Protocol error` ou
> `Cannot create symlink` :
> 1. activez le **Mode développeur** (*Paramètres → Système → Espace développeur*), **ou**
> 2. lancez le terminal **en tant qu'administrateur** puis relancez la commande.
>
> Cette étape n'a **pas pu être testée** sur un poste Windows par l'auteur de la
> configuration ; signalez tout comportement différent.

### 2.6 Lancer l'application

Ouvrez **trois terminaux** dans le dossier du projet :

```powershell
# Terminal 1 — serveur web
php artisan serve
```

```powershell
# Terminal 2 — compilation des assets
npm run dev
```

```powershell
# Terminal 3 — file d'attente
php artisan queue:work
```

L'application est disponible sur **http://localhost:8000**.

> **Le terminal 3 n'est pas optionnel.** Les callbacks de la passerelle de
> paiement simulée transitent par la file d'attente : sans `queue:work`, aucun
> paiement ne sera jamais confirmé.

Pour les tâches planifiées (réconciliation des paiements, annulation des
commandes non payées, expiration des abonnements), ajoutez un quatrième
terminal :

```powershell
php artisan schedule:work
```

---

## 3. Vérifier que tout fonctionne

```powershell
php artisan test
.\vendor\bin\pint --test
.\vendor\bin\phpstan analyse
```

Les trois commandes doivent se terminer sans erreur. Raccourci équivalent :

```powershell
composer test
```

---

## 4. Comptes de démonstration

Créés par `php artisan migrate:fresh --seed`. **Le mot de passe est `password`
pour tous.**

| Rôle | Identifiant | État du compte |
|---|---|---|
| Administrateur | `admin@agritech.local` | Actif, tous les privilèges |
| Client | `client@agritech.local` | Actif, a une commande payée et une formation achetée |
| Client | `client2@agritech.local` | Actif, **panier en cours**, abonnement trimestriel, commande impayée |
| Agriculteur | `agriculteur@agritech.local` | **Actif** (validé) — Ferme du Mbam, Obala |
| Agricultrice | `agricultrice@agritech.local` | **Actif** (validé) — Coopérative des Hauts Plateaux, Dschang |
| Agriculteur | `agriculteur-attente@agritech.local` | **En attente de validation** — à approuver depuis l'admin |
| Agricultrice | `agriculteur-impaye@agritech.local` | **En attente de paiement** des frais d'inscription |
| Agriculteur | `agriculteur-refuse@agritech.local` | **Refusé**, avec motif enregistré |

### Ce que contient le jeu de démonstration

- **7 produits** répartis sur deux agriculteurs, dont **un en attente de
  modération** (« Ananas de Bafia ») pour tester l'écran d'administration.
- **4 formations**, dont trois incluses dans l'abonnement.
- **3 commandes** : une payée **répartie entre deux agriculteurs** (deux
  sous-commandes), une en attente de paiement, une annulée.
- **Un panier en cours** (deux produits, sur `client2@agritech.local`), pour
  dérouler la commande sans chercher un produit d'abord.
- **18 photographies réelles** : 13 pour les produits, 4 pour les formations,
  1 pour la page d'accueil. Elles sont **dans le dépôt** (`database/seeders/photos`
  et `public/images`, 2,9 Mo), donc l'amorçage n'a besoin d'aucune connexion.
  Licences CC0, domaine public ou CC BY ; auteurs et sources listés dans
  `database/seeders/photos/CREDITS.md` et sur la page **`/credits-photos`**,
  liée depuis le pied de page. Sans l'extension GD et sans ces fichiers, les
  écrans retombent sur leur cadre vide — rien ne casse.
- **4 paiements**, une souscription active, une conversation avec un message
  non lu côté agriculteur.

> Ces comptes sont destinés à une base locale de démonstration. Les mots de
> passe sont volontairement triviaux et ne doivent jamais servir ailleurs.

### Se connecter par téléphone

Le champ de connexion accepte **une adresse e-mail ou un numéro de téléphone**.
Le numéro peut être saisi de toutes les façons usuelles — `650000001`,
`650 00 00 01`, `+237 650 00 00 01`, `00237650000001` — il est normalisé avant
la recherche.

| Compte | Numéro |
|---|---|
| Administrateur | `600 00 00 01` |
| Client | `650 00 00 01` |
| Client (abonné) | `650 00 00 02` |
| Agriculteur (validé) | `670 00 00 01` |
| Agricultrice (validée) | `690 00 00 02` |
| Agriculteur en attente de validation | `680 00 00 03` |
| Agricultrice en attente de paiement | `670 00 00 04` |
| Agriculteur refusé | `690 00 00 05` |

Les comptes **suspendu**, **refusé** et **supprimé** ne peuvent pas se
connecter : ils reçoivent un message qui explique pourquoi, et non un
« identifiants incorrects » trompeur.

### Parcours à essayer

| Parcours | Chemin |
|---|---|
| Inscription client | `/register` |
| **Inscription agriculteur** | `/inscription/agriculteur` |
| Statut d'un compte non actif | `/mon-compte/statut` |
| Catalogue public | `/catalogue` |
| **Formations publiques** | `/formations` |
| Espace client | `/client/tableau-de-bord` |
| **Panier** | `/client/panier` |
| **Mes commandes** | `/client/commandes` |
| **Mes formations** | `/client/formations` |
| **Abonnement** | `/client/abonnement` |
| **Messagerie client** | `/client/messages` |
| Espace agriculteur | `/agriculteur/tableau-de-bord` |
| **Mes produits** | `/agriculteur/produits` |
| **Mes formations** | `/agriculteur/formations` |
| **Commandes reçues** | `/agriculteur/commandes` |
| **Messagerie agriculteur** | `/agriculteur/messages` |
| Administration | `/admin/tableau-de-bord` |
| Comptes agriculteurs à valider | `/admin/agriculteurs-a-valider` |
| Utilisateurs | `/admin/utilisateurs` |
| Privilèges | `/admin/privileges` |
| Paramètres de la plateforme | `/admin/parametres` |
| Journal d'audit | `/admin/journal-audit` |

### Parcours formations et abonnement

Connectez-vous avec `client@agritech.local` : il a **acheté** la formation
« Composter ses déchets agricoles ». Ouvrez-la depuis **Mes formations** : le
bouton **Ouvrir** sert le fichier. Un visiteur, ou un client sans achat, voit
les mêmes titres de modules — mais verrouillés.

Connectez-vous avec `client2@agritech.local` : il a un **abonnement
trimestriel actif**. Les formations marquées « incluse dans l'abonnement »
(`Irrigation goutte à goutte`, `Entretenir une cacaoyère`, `Conserver les
récoltes`) sont ouvertes sans achat.

Pour acheter une formation de bout en bout : `/formations` → choisissez une
formation → **Acheter** (opérateur + numéro) → la page de test → **Paiement
réussi** → le callback arrive quelques secondes plus tard (`queue:work` doit
tourner) → l'accès s'ouvre. Le contenu est ensuite servi par le contrôleur,
uniquement aux ayants droit — l'URL directe du fichier ne fonctionne pas, et
le chemin n'apparaît nulle part côté client.

Pour souscrire : **Abonnement** → **Choisir un plan** → payer. Le terme ne
démarre qu'à la confirmation du paiement ; un abonnement en cours bloque toute
nouvelle souscription jusqu'à son expiration (tâche planifiée toutes les cinq
minutes, ou `php artisan agritech:subscriptions:expire`).

### Messagerie

Depuis une fiche produit, le bouton **Contacter l'agriculteur** ouvre (ou
retrouve) le fil avec la ferme qui vend. Un fil par paire client ↔ agriculteur,
un badge de messages non lus dans la navigation, un rafraîchissement toutes les
cinq secondes. Les notifications arrivent en base et par e-mail — visible dans
`storage/logs/laravel.log` avec le mailer `log`.

Connectez-vous avec `agriculteur-impaye@agritech.local` pour dérouler le
paiement des frais d'inscription de bout en bout — voir le § 5. Le parcours
panier → commande → paiement → préparation est détaillé au § 8.

---

## 5. Passerelle de paiement simulée

Aucun opérateur Mobile Money réel n'est contacté, aucun argent ne circule. Une
passerelle interne reproduit le cycle complet d'un paiement.

```
Écran « statut de mon compte »
  → montant lu côté serveur, jamais du formulaire
  → page de paiement de test
      [Confirmer]  [Refuser]  [Laisser expirer]
  → job en file d'attente, latence configurable
  → callback signé HMAC vers /webhooks/paiements
  → signature vérifiée, idempotence, payload journalisé
  → le compte agriculteur passe en « en attente de validation »
```

> **`php artisan queue:work` doit tourner.** La confirmation passe par la file
> d'attente, comme la réponse d'un vrai opérateur. Sans worker, aucun paiement
> n'aboutit. C'est voulu : exécuter le callback en ligne masquerait tous les
> défauts qui n'apparaissent que lorsque la réponse arrive en différé.

### Numéros de test

| Numéro | Comportement, quel que soit le bouton cliqué |
|---|---|
| `670 00 00 00` | **échoue toujours** |
| `670 00 00 99` | **n'aboutit jamais** — force le passage par la réconciliation |
| tout autre | suit le bouton choisi |

### Piloter un paiement depuis le terminal

```powershell
# Forcer une issue (la référence est affichée sur la page de paiement)
php artisan agritech:payment:simulate PAY-XXXXXXXXXXXXXXXX succeeded
php artisan agritech:payment:simulate PAY-XXXXXXXXXXXXXXXX failed

# Sans attendre la latence configurée
php artisan agritech:payment:simulate PAY-XXXXXXXXXXXXXXXX succeeded --now

# Envoyer DEUX FOIS le même callback : le paiement ne doit bouger qu'une fois
php artisan agritech:payment:simulate PAY-XXXXXXXXXXXXXXXX succeeded --duplicate --now

# Clôturer les paiements restés sans réponse
php artisan agritech:payments:reconcile
php artisan agritech:payments:reconcile --minutes=1

# Expirer les abonnements dont le terme est passé (sinon planifié toutes les 5 min)
php artisan agritech:subscriptions:expire

# Remettre le jeu de démonstration à neuf
php artisan agritech:reset-demo --force
```

### Essayer le parcours complet

1. Connectez-vous avec `agriculteur-impaye@agritech.local` (mot de passe `password`).
2. Sur l'écran de statut, choisissez un opérateur et validez.
3. Sur la page de test, cliquez **Confirmer**.
4. L'écran d'attente se met à jour seul dès que le callback est traité.
5. Le compte passe en **« en attente de validation »**.

> **Ce que cet écran ne fait pas.** Revenir dessus, le recharger ou le laisser
> ouvert ne fera jamais aboutir un paiement. Seul un callback vérifié côté
> serveur — ou la réconciliation — peut le confirmer. C'est la règle RG06, et
> elle vaut aussi pour la passerelle simulée.

### Vérifier soi-même que la signature protège vraiment

```powershell
# Callback forgé : doit répondre 403 et ne rien changer
curl -X POST http://localhost:8000/webhooks/paiements `
  -H "Content-Type: application/json" `
  -H "X-AgriTech-Signature: 0000000000000000000000000000000000000000000000000000000000000000" `
  -H "X-AgriTech-Timestamp: 1789000000" `
  -d '{"event_id":"evt_test","reference":"PAY-XXXX","status":"succeeded","amount":10000,"currency":"XAF","occurred_at":"2026-01-01T00:00:00+01:00"}'
```

Le rejet est tracé dans `storage/logs/laravel.log`.

---

## 6. Administration

Le rôle administrateur ouvre `/admin` ; **ce sont les privilèges qui autorisent
chaque action**. Le super-administrateur de démonstration les détient tous.

### Voir le cloisonnement à l'œuvre

Depuis `/admin/privileges`, retirez des privilèges à un autre administrateur,
puis connectez-vous avec son compte : les écrans correspondants renvoient une
erreur 403 et disparaissent de sa navigation. Masquer le lien ne suffit jamais —
essayez l'URL directement.

### Suppression d'un compte

Elle est **logique** : le compte passe en « supprimé », son nom devient
« Compte supprimé », son adresse et son numéro sont mis à `NULL`. Ses commandes
et ses paiements restent consultables, et son adresse redevient disponible pour
une nouvelle inscription.

Un administrateur ne peut ni se suspendre, ni se supprimer, ni modifier ses
propres privilèges.

### Journal d'audit

Chaque action sensible y figure avec son auteur, son horodatage et le détail
avant/après. La suppression d'un compte n'y recopie pas l'adresse qu'elle vient
d'effacer — seulement le fait qu'il y en avait une.

---

## 7. Catalogue et publication

### Côté visiteur

`/catalogue` est ouvert sans compte : recherche, filtres par catégorie et par
région, tri. `/produits/{slug}` affiche la fiche.

**Ce qui n'y apparaît pas :** les brouillons, les fiches en cours de
modération, les fiches refusées ou archivées, et **les produits d'un
agriculteur suspendu ou supprimé** — suspendre un compte doit l'empêcher de
vendre, sinon la sanction ne sert à rien.

### Côté agriculteur

Connectez-vous avec `agriculteur@agritech.local` puis allez dans
**Mes produits**. Une fiche est enregistrée en **brouillon**, puis soumise à
publication. Selon le paramètre *Modération a priori* (écran des paramètres
admin), elle part en modération ou paraît immédiatement.

Un refus affiche son motif directement sur la fiche, à corriger puis
resoumettre.

### Les images

Elles sont stockées sur un disque **privé** et servies par une route
applicative, **jamais par `storage:link`**. Conséquence utile : même si le lien
symbolique échoue sur votre machine, les images fonctionnent.

Contrôles appliqués : type réel du fichier (un script PHP renommé `.jpg` est
refusé), 4 Mo maximum, 4000 × 4000 pixels maximum, 5 images par produit, et
renommage systématique — le nom que vous choisissez n'est jamais conservé.

### Côté administrateur

`/admin/publications-a-moderer` liste ce qui attend une décision, produits et
formations confondus. `/admin/categories` gère les catégories ; une catégorie
qui contient des produits ne peut pas être supprimée, et l'écran le dit au lieu
de laisser passer une erreur.

---

## 8. Panier, commandes et livraison

### Le panier

`/client/panier`, réservé à un compte **client** connecté. Un visiteur qui
clique « Ajouter au panier » est envoyé se connecter puis ramené sur la fiche.

Le panier est **en base**, pas en session : un panier qui disparaît au moindre
rechargement sur un téléphone est un panier qui ne devient jamais une commande.
Les lignes sont regroupées **par agriculteur**, comme le sera la commande.

Une ligne devenue indisponible (produit dépublié, agriculteur suspendu, stock
descendu sous la quantité demandée) est **signalée et exclue du total**, jamais
supprimée en silence. Tant qu'une ligne est signalée, le bouton « Commander »
reste inactif : une commande passe en une seule fois ou pas du tout.

### Passer commande (RG03)

« Commander » crée la commande sans rien payer et **sans toucher au stock**.
Ce qu'elle fige au passage :

| Figé | Pourquoi |
|---|---|
| Le prix unitaire de chaque ligne | Un agriculteur qui change son prix demain ne doit pas réécrire une commande d'hier |
| Le taux de commission | Un administrateur qui relève la commission ne doit pas réécrire une comptabilité déjà convenue |
| La référence `CMD-AAAA-NNNNNN` | Lisible au téléphone, séquence remise à zéro chaque année |

Une commande multi-agriculteurs donne **une sous-commande par agriculteur**
(`CMD-2026-000123-A`, `-B`…) : c'est l'unité réellement préparée et livrée.

Les quantités demandées sont vérifiées **sous verrou de ligne** au moment de la
création. Si un produit est devenu insuffisant entre l'ajout au panier et le
clic, la commande est refusée en entier et le panier est conservé tel quel.

### Payer (RG04, RG06)

Depuis la fiche de la commande : opérateur, numéro, puis la passerelle simulée
(voir le § 5). **Le montant n'est pas envoyé par le formulaire** — le serveur le
lit sur la commande.

Cliquer deux fois sur « Payer » ne crée pas deux paiements : le client est
renvoyé vers celui qui est déjà en cours de vérification.

**Le stock ne bouge qu'à la confirmation vérifiée**, dans la transaction qui
confirme le paiement, sous `lockForUpdate`. Deux paiements simultanés sur le
dernier lot sont donc sérialisés par la base, et non par la chance :

- le premier confirmé emporte le stock et la commande passe en **payée** ;
- le second trouve le stock insuffisant : sa commande est **annulée en entier**
  et son paiement **remboursé**, avec un message qui nomme le produit en cause.

> Une livraison partielle n'a pas été retenue : elle obligerait à inventer un
> remboursement au prorata que personne n'a demandé.

Même traitement pour un paiement qui arrive **après** l'annulation automatique
de sa commande.

### Annulation automatique

Une commande impayée est annulée passé le délai du paramètre *Annulation des
commandes non payées* (30 minutes par défaut) :

```powershell
php artisan agritech:orders:cancel-expired
```

Planifiée toutes les cinq minutes par `php artisan schedule:work`. Une commande
dont **un paiement est encore en cours de vérification** est laissée tranquille :
l'annuler sous un paiement lent le transformerait en remboursement.

### Côté agriculteur

`/agriculteur/commandes` montre **sa part et rien d'autre** : ce que le même
client a acheté ailleurs ne le regarde pas. Chaque ligne affiche le sous-total,
la commission figée et **ce qui lui revient**.

Les transitions `payée → en préparation → livrée` sont les siennes. La commande
du client ne passe en *livrée* que lorsque **tous** les agriculteurs concernés
ont livré.

### Essayer le parcours complet

1. Connectez-vous avec `client2@agritech.local` (mot de passe `password`) : son
   panier contient déjà deux produits.
2. **Mon panier** → **Commander**.
3. Sur la commande, choisissez un opérateur et validez.
4. Sur la page de la passerelle, choisissez **Paiement réussi**.
5. `php artisan queue:work` doit tourner : le callback arrive quelques secondes
   plus tard, la commande passe en **payée** et le stock diminue.
6. Connectez-vous avec l'agriculteur concerné : la commande est dans
   **Commandes reçues**.

Pour voir le remboursement : avant l'étape 4, mettez le stock du produit à zéro
(`php artisan tinker`), puis confirmez le paiement.

---

## 9. Structure du projet

```
app/
  Enums/          statuts et types (enums PHP natifs)
  Models/
  Http/           contrôleurs fins, Form Requests, middleware
  Livewire/       composants d'interface par domaine
  Policies/
  Services/       logique métier
  Payments/       contrat PaymentGateway + passerelle simulée
  Notifications/  Jobs/  Support/
database/         migrations/ seeders/ factories/
lang/fr/          messages en français
resources/views/  layouts par rôle : public, client, farmer, admin
tests/Unit  tests/Feature
```

Documents de référence à la racine :

- **`CLAUDE.md`** — stack, conventions, règles de gestion, commandes
- **`DECISIONS.md`** — journal des décisions et hypothèses
- **`ROADMAP.md`** — avancement phase par phase

---

## 10. Problèmes fréquents

| Symptôme | Cause probable | Solution |
|---|---|---|
| `Vite manifest not found` | Assets non compilés | `npm run build` (étape 5) |
| Un paiement reste `pending` | File d'attente non lancée | `php artisan queue:work` |
| `SQLSTATE[HY000] [1049] Unknown database` | Base non créée | Voir § 2.2 |
| `Access denied ... agritech_test_test_1` | L'utilisateur MySQL ne peut pas créer de bases | Utiliser `root`, ou accorder les droits sur `agritech_test%` |
| `Cannot create symlink` | Droits Windows | Voir § 2.5 |
| Composer exige PHP 8.4 | Mauvaise version PHP active | Laragon → *Menu → PHP → Version* |
