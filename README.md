# AgriTech

Plateforme web qui met en relation **agriculteurs** et **clients** au Cameroun :
vente de produits agricoles et de formations, abonnements, messagerie, et
paiements Mobile Money **simulés localement**.

> **Application 100 % locale.** Aucun déploiement en ligne, aucun service
> externe, aucun compte tiers. Une fois les dépendances installées,
> l'application fonctionne **sans connexion Internet**.

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
| Client | `client2@agritech.local` | Actif, a un abonnement trimestriel en cours |
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
- **4 paiements**, une souscription active, une conversation avec un message
  non lu côté agriculteur.

> Ces comptes sont destinés à une base locale de démonstration. Les mots de
> passe sont volontairement triviaux et ne doivent jamais servir ailleurs.

---

## 5. Passerelle de paiement simulée

Aucun opérateur Mobile Money réel n'est contacté. Une passerelle interne
reproduit fidèlement le cycle d'un paiement : initiation, page de paiement,
décision, callback signé côté serveur, vérification, puis effet métier.

*Le détail du fonctionnement, les numéros de test et la commande
`agritech:payment:simulate` seront documentés ici à l'issue de la phase 3.*

---

## 6. Structure du projet

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

## 7. Problèmes fréquents

| Symptôme | Cause probable | Solution |
|---|---|---|
| `Vite manifest not found` | Assets non compilés | `npm run dev` ou `npm run build` |
| Un paiement reste `pending` | File d'attente non lancée | `php artisan queue:work` |
| `SQLSTATE[HY000] [1049] Unknown database` | Base non créée | Voir § 2.2 |
| `Access denied ... agritech_test_test_1` | L'utilisateur MySQL ne peut pas créer de bases | Utiliser `root`, ou accorder les droits sur `agritech_test%` |
| `Cannot create symlink` | Droits Windows | Voir § 2.5 |
| Composer exige PHP 8.4 | Mauvaise version PHP active | Laragon → *Menu → PHP → Version* |
