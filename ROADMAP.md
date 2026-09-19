# ROADMAP.md — feuille de route AgriTech

Chaque phase se termine par une démonstration en local et une validation
explicite avant de passer à la suivante.

---

## Phase 0 — Installation et socle ✅

- [x] Vérification de l'environnement (PHP 8.4, Composer 2.8, Node 22, MariaDB 10.11)
- [x] Création du projet Laravel 13.32 à partir du Livewire Starter Kit officiel
- [x] Sélection des fonctionnalités d'authentification (inscription, réinitialisation, vérification d'e-mail, confirmation du mot de passe)
- [x] Retrait de `laravel/sail` (Docker interdit) et de `laravel/chisel`
- [x] Configuration `.env` / `.env.example` locale (fr, Africa/Douala, MariaDB, file d'attente `database`, mail `log`, variables de la passerelle simulée)
- [x] Tailwind 4 + Vite 8 opérationnels, polices empaquetées localement (fonctionne hors ligne)
- [x] Pest 5 installé et configuré (`tests/Pest.php`, base de tests `agritech_test` sur moteur réel)
- [x] Laravel Pint configuré (preset `laravel`)
- [x] Larastan configuré au **niveau 7**
- [x] Fichiers de langue `lang/fr` (111 clés de validation traduites) et `lang/fr.json`
- [x] Squelette de dossiers conforme à l'architecture cible
- [x] `CLAUDE.md`, `DECISIONS.md`, `ROADMAP.md`, `README.md`

**Acceptation :** `php artisan test`, `./vendor/bin/pint --test` et
`./vendor/bin/phpstan analyse` passent ; l'application démarre sur
`http://localhost:8000`.

> **Reste à faire, reporté en phase 2 :** les vues du starter kit contiennent
> encore des chaînes anglaises en dur. Leur traduction est traitée avec le reste
> de l'authentification.

---

## Phase 1 — Modèle de données ⬜

- [ ] Enums PHP natifs pour tous les statuts (`UserStatus`, `UserRole`, `PublicationStatus`, `OrderStatus`, `PaymentStatus`, `PaymentPurpose`, `SubscriptionStatus`, `PaymentMethod`)
- [ ] Migrations : `users` (étendue), `farmer_profiles`, `privileges`, `privilege_user`, `categories`, `products`, `trainings`, `orders`, `sub_orders`, `order_items`, `training_purchases`, `subscription_plans`, `subscriptions`, `payments`, `conversations`, `messages`, `audit_logs`, `settings`, `notifications`
- [ ] Modèles Eloquent : relations, `casts`, `$fillable`
- [ ] Classe `Support\Money` (entiers FCFA, formatage `12 500 FCFA`)
- [ ] Méthodes de transition de statut explicites, refusant les transitions invalides
- [ ] Factories pour chaque modèle
- [ ] Seeders : super-admin, privilèges, catégories, plans d'abonnement, jeu de démonstration réaliste
- [ ] `preventLazyLoading` activé en local

**Acceptation :** `php artisan migrate:fresh --seed` reproductible ; tests des
relations et des transitions de statut au vert.

---

## Phase 2 — Authentification et comptes ⬜

- [ ] Inscription client
- [ ] Inscription agriculteur (informations d'exploitation, unicité e-mail/téléphone)
- [ ] Connexion par **e-mail ou téléphone** + mot de passe
- [ ] Déconnexion, réinitialisation du mot de passe (e-mail visible en local)
- [ ] Vérification d'e-mail (optionnelle, activable)
- [ ] Profil utilisateur
- [ ] Middleware et layouts par rôle (public, client, farmer, admin)
- [ ] **Traduction française complète des vues d'authentification et de réglages**
- [ ] Rate limiting sur connexion, inscription et réinitialisation

**Acceptation :** des tests prouvent qu'aucun rôle n'accède aux routes d'un autre.

---

## Phase 3 — Passerelle de paiement simulée ⬜

- [ ] Interface `PaymentGateway` (`initiate`, `verifyCallback`, `getStatus`, `refund`)
- [ ] `FakeMobileMoneyGateway` activée par `PAYMENT_GATEWAY=fake`
- [ ] Page de paiement de test (mention « Environnement de test », MTN MoMo / Orange Money, boutons Confirmer / Refuser / Laisser expirer)
- [ ] Callback signé HMAC, envoyé par un job en file d'attente avec latence configurable
- [ ] Route webhook : vérification de signature, **idempotence**, journalisation du payload brut
- [ ] Commande `agritech:payment:simulate {reference} {succeeded|failed|expired}`
- [ ] Option d'envoi dupliqué du callback (test d'idempotence)
- [ ] Tâche de réconciliation planifiée pour les paiements restés `pending`
- [ ] Branchement sur les frais d'inscription agriculteur (RG02)
- [ ] Numéros de test documentés dans le README

**Acceptation :** parcours d'inscription agriculteur jusqu'à `pending_validation` ;
scénarios succès, échec, expiration et callback dupliqué testés. **RG06 couverte.**

---

## Phase 4 — Administration ⬜

- [ ] Tableau de bord admin
- [ ] Approbation / refus motivé des comptes agriculteurs
- [ ] Suspension et suppression logique (statut `deleted` + anonymisation)
- [ ] Gestion des privilèges
- [ ] Journal d'audit (qui, quoi, quand, avant/après)
- [ ] Écran des paramètres (frais d'inscription, commission, délais, modération a priori)

**Acceptation :** RG07, RG08 et RG11 couvertes par des tests.

---

## Phase 5 — Catalogue et publication ⬜

- [ ] CRUD produits et catégories
- [ ] Upload d'images (validation du type MIME réel, taille, renommage)
- [ ] Circuit de modération `in_review` → `published` / `rejected`
- [ ] Catalogue public, recherche et filtres, pages produit

**Acceptation :** RG01 et RG09 testées ; catalogue utilisable à 360 px.

---

## Phase 6 — Panier et commandes ⬜

- [ ] Panier multi-agriculteurs
- [ ] Commande + sous-commandes par agriculteur
- [ ] Paiement simulé de la commande
- [ ] Décrément du stock sous `lockForUpdate`, dans une transaction
- [ ] Suivi des commandes côté client et côté agriculteur
- [ ] Annulation automatique des commandes non payées (tâche planifiée)

**Acceptation :** RG03, RG04 et RG06 testées, **y compris un test de concurrence
sur le stock**.

---

## Phase 7 — Formations ⬜

- [ ] Publication et upload des contenus (vidéo, PDF)
- [ ] Achat d'une formation
- [ ] Accès protégé par contrôleur (jamais d'URL publique directe)
- [ ] Espace « Mes formations »

**Acceptation :** RG05 testée ; aucun contenu payant accessible sans droit.

---

## Phase 8 — Abonnements ⬜

- [ ] Plans d'abonnement
- [ ] Souscription et paiement simulé
- [ ] Accès aux formations incluses
- [ ] Expiration automatique (tâche planifiée)

---

## Phase 9 — Messagerie et notifications ⬜

- [ ] Conversations client ↔ agriculteur
- [ ] Compteur de messages non lus
- [ ] Notifications en base et par e-mail (visibles en local)

---

## Phase 10 — Tableaux de bord ⬜

- [ ] Statistiques de ventes pour l'agriculteur
- [ ] Activité globale pour l'administrateur

---

## Phase 11 — Finalisation locale ⬜

- [ ] Revue de sécurité
- [ ] Performances : pagination, eager loading, images
- [ ] Accessibilité
- [ ] Suite de tests complète
- [ ] `README.md` d'installation pas à pas sous Windows, à partir de zéro
- [ ] Liste des comptes de démonstration (un par rôle)
- [ ] Guide de test manuel couvrant chaque cas d'utilisation, paiements réussis, échoués et expirés inclus
- [ ] Commande `agritech:reset-demo`

**Acceptation :** une personne qui découvre le projet peut l'installer et tester
tous les parcours en suivant uniquement le `README.md`.
