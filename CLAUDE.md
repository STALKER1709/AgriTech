# CLAUDE.md — mémoire du projet AgriTech

Ce fichier est la référence permanente du projet. À lire avant toute intervention.

## 1. Le produit

**AgriTech** met en relation des **agriculteurs** et des **clients** au Cameroun.
Les agriculteurs vendent des produits agricoles et des formations ; les clients
achètent, s'abonnent et échangent par messagerie ; un administrateur valide les
comptes agriculteurs et modère les publications.

- Devise : **FCFA (XAF)**, stockée en **entiers** — jamais de `float`.
- Fuseau : **Africa/Douala**. Locale : **fr**.
- Usage majoritairement mobile : l'interface doit rester utilisable à **360 px**.
- Les paiements sont **simulés localement** (Mobile Money factice). Aucun
  opérateur réel n'est contacté, aucun service en ligne n'est requis.

## 2. Contraintes non négociables

- **100 % local.** Pas de Docker, pas de cloud, pas d'API tierce nécessitant un
  compte ou une clé. L'application doit tourner **sans connexion Internet** une
  fois les dépendances installées.
- **Aucun secret dans git.** `.env.example` reste complet et à jour.
- L'environnement cible du développeur est **Windows** (Laragon). Les commandes
  documentées doivent fonctionner sous PowerShell, sans outil Unix.
- **Interface, messages et contenus en français** ; **code en anglais**
  (classes, méthodes, variables, commentaires techniques).

## 3. Stack

| Élément | Version / choix |
|---|---|
| PHP | **8.4** (Laravel 13 accepte 8.3, mais Pest 5 exige 8.4) |
| Laravel | **13.x** |
| Base de données | MySQL 8 / MariaDB — base applicative `agritech`, base de tests `agritech_test` |
| Front | Blade + **Livewire 4** (composants de classe) + **Flux 2** + **Tailwind 4**, build **Vite 8** via `vite-plus` |
| Authentification | **Livewire Starter Kit** officiel, propulsé par **Laravel Fortify** |
| Tests | **Pest 5** (sur PHPUnit 13) |
| Formatage | **Laravel Pint** (preset `laravel`) |
| Analyse statique | **Larastan** niveau **7** |
| File d'attente | driver `database` (`php artisan queue:work`) |
| Tâches planifiées | `php artisan schedule:work` |
| E-mails | driver `log` (ou Mailpit si disponible) |

## 4. Architecture

```
app/
  Enums/          statuts et types (enums PHP natifs)
  Models/
  Http/
    Controllers/  fins, délèguent aux services
    Requests/     Form Requests
    Middleware/
  Livewire/       composants par domaine
  Policies/
  Services/       logique métier : Auth, Catalog, Orders, Trainings,
                  Subscriptions, Payments, Messaging, Admin
  Payments/
    Contracts/PaymentGateway.php
    Gateways/FakeMobileMoneyGateway.php
  Notifications/
  Jobs/
  Support/        Money, helpers
database/         migrations/ seeders/ factories/
resources/views/  layouts par rôle : public, client, farmer, admin
routes/web.php    groupes par rôle et middleware
tests/Unit  tests/Feature
```

### Principes

- **La logique métier vit dans les services**, jamais dans les contrôleurs, les
  composants Livewire ou les vues.
- **Toute autorisation est vérifiée côté serveur** (Policies, Gates,
  middleware). Masquer un bouton ne suffit jamais.
- Les transitions de statut passent par des **méthodes de transition
  explicites** qui refusent les transitions invalides, et sont testées.
- Les opérations multi-tables s'exécutent dans `DB::transaction()`.
- Les montants sont formatés par une classe `Money` (`12 500 FCFA`).
- Factory + seeder pour chaque modèle.

## 5. Règles de gestion

- **RG01** — Un agriculteur ne peut rien publier tant que son compte n'est pas `active`.
- **RG02** — Un compte agriculteur ne passe en `pending_validation` qu'après paiement réussi des frais d'inscription.
- **RG03** — Une commande n'est créée que si la quantité demandée est disponible.
- **RG04** — Le stock n'est décrémenté qu'après confirmation vérifiée du paiement, dans une transaction avec `lockForUpdate`. Deux paiements simultanés ne doivent jamais rendre un stock négatif.
- **RG05** — L'accès au contenu d'une formation exige un paiement réussi ou un abonnement actif incluant la formation.
- **RG06** — Un paiement n'est `succeeded` que sur **confirmation vérifiée côté serveur** (webhook signé ou interrogation de la passerelle), **jamais** sur une simple redirection du navigateur. S'applique aussi à la passerelle simulée.
- **RG07** — Seul un administrateur disposant du privilège requis peut approuver, suspendre ou supprimer un compte, modérer une publication ou modifier des privilèges.
- **RG08** — La suppression d'un utilisateur est **logique** (statut `deleted` + anonymisation), pour conserver l'historique des commandes et des paiements.
- **RG09** — Toute nouvelle publication passe par `in_review` avant `published`, sauf si la modération a priori est désactivée.
- **RG10** — Tous les montants sont des **entiers** en FCFA.
- **RG11** — Toute action administrative sensible est tracée dans le journal d'audit (qui, quoi, quand, avant/après).

## 6. Commandes

| But | Commande |
|---|---|
| Installer les dépendances PHP | `composer install` |
| Installer les dépendances front | `npm install` |
| Migrer et peupler | `php artisan migrate:fresh --seed` |
| Serveur de développement | `php artisan serve` |
| Compilation des assets | `npm run dev` (ou `npm run build`) |
| Tests | `php artisan test` ou `./vendor/bin/pest --parallel` |
| Formatage | `./vendor/bin/pint` |
| Analyse statique | `./vendor/bin/phpstan analyse` |
| Tout vérifier | `composer test` |
| File d'attente | `php artisan queue:work` |
| Planificateur | `php artisan schedule:work` |

> Les callbacks de la passerelle de paiement simulée passent par la file
> d'attente : **`php artisan queue:work` doit tourner** pour que les paiements
> aboutissent.

## 7. Méthode de travail

1. Présenter un plan avant de coder une phase ; attendre la validation.
2. S'arrêter à la fin de chaque phase : récapitulatif, commandes de test,
   comptes de démonstration, critères d'acceptation vérifiés.
3. Avant de déclarer une tâche terminée : **tests verts, Pint propre, Larastan
   sans erreur**.
4. Une exigence ambiguë se **pose en question** ; une hypothèse retenue pour
   avancer se consigne dans `DECISIONS.md`.
5. Commits atomiques, format Conventional Commits (`feat:`, `fix:`, `chore:`,
   `docs:`, `test:`, `refactor:`).
6. Mettre à jour `ROADMAP.md` et, si besoin, `DECISIONS.md` à chaque phase.

## 8. Définition de « terminé »

- Entrées validées, autorisations vérifiées côté serveur
- Cas nominal **et** cas d'erreur gérés, messages clairs en français
- États de chargement et d'erreur présents dans l'interface
- Tests écrits et au vert ; Pint et Larastan sans erreur
- Interface utilisable sur un écran de 360 px
- Fonctionne en local, sans connexion Internet
- `ROADMAP.md` (et `DECISIONS.md` si besoin) à jour
