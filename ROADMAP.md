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

> La traduction française des vues du starter kit, annoncée ici comme reste à
> faire, a été réalisée en **phase 2**.

---

## Phase 1 — Modèle de données ✅

- [x] 13 enums PHP natifs pour les statuts et les types, avec libellés français et transitions autorisées
- [x] 16 migrations : `users` (réécrite), `farmer_profiles`, `privileges` + pivot, `categories`, `products` + images, `trainings` + contenus, `orders` / `sub_orders` / `order_items`, `training_purchases`, `subscription_plans` / `subscriptions`, `payments`, `conversations` / `messages`, `audit_logs`, `settings`, `notifications`
- [x] 19 modèles Eloquent : relations, `casts`, `#[Fillable]`, scopes
- [x] Objet-valeur `Support\Money` (entiers FCFA, formatage `12 500 FCFA`) et son `MoneyCast`
- [x] Objet-valeur `Support\Quantity` (entiers de millièmes) et son `QuantityCast`, sans `bcmath`
- [x] Méthodes de transition explicites levant `InvalidStatusTransition`
- [x] 19 factories avec états nommés
- [x] Seeders : privilèges, super-admin, catégories, paramètres, plans d'abonnement, jeu de démonstration
- [x] `preventLazyLoading` actif en environnement local
- [x] 133 tests ajoutés (`Money`, `Quantity`, transitions, relations, intégrité, seeders)

**Acceptation :** `php artisan migrate:fresh --seed` exécuté deux fois de suite
donne le même résultat · aucune colonne monétaire n'est un flottant, vérifié par
un test lisant `information_schema` · `composer test` intégralement propre
(Pint, Larastan niveau 7, 162 tests).

---

## Phase 2 — Authentification et comptes ✅

- [x] Inscription client (Fortify) créant un compte `client` actif
- [x] Inscription agriculteur : parcours dédié, `User` + `FarmerProfile` créés en transaction, statut `pending_payment`
- [x] Connexion par **e-mail ou téléphone** sur un champ unique, cinq écritures du même numéro testées
- [x] Refus explicite des comptes suspendus, refusés et supprimés
- [x] Déconnexion, réinitialisation du mot de passe (e-mail visible dans `storage/logs`)
- [x] Objet-valeur `Support\PhoneNumber` + règles `CameroonPhoneNumber` et `UniquePhoneNumber`
- [x] Normalisation systématique du téléphone à l'écriture (mutateur sur `User`)
- [x] Middlewares `role` et `account.active`, espaces `/client`, `/agriculteur`, `/admin`
- [x] `/dashboard` devient un aiguillage vers l'espace du rôle
- [x] Écran « statut de mon compte » avec le montant des frais d'inscription
- [x] Navigation par rôle dans un shell partagé
- [x] **Traduction française complète** des vues d'authentification et de réglages
- [x] Limitation de débit sur la connexion, l'inscription et la réinitialisation
- [x] 73 tests ajoutés (cloisonnement, connexion, inscription agriculteur, téléphone)

**Acceptation :** aucun rôle n'accède aux routes d'un autre — visiteur redirigé,
rôle étranger en 403, agriculteur non actif renvoyé vers son écran de statut ·
connexion vérifiée par e-mail et par téléphone dans l'application réelle ·
plus aucune chaîne anglaise en dur dans les vues · `composer test` intégralement
propre (Pint, Larastan niveau 7, 235 tests).

> **Reporté en phase 3 :** le bouton de paiement des frais d'inscription est
> désactivé et signalé comme tel. Il sera branché sur la passerelle simulée,
> qui fera passer le compte en `pending_validation` (RG02).

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
