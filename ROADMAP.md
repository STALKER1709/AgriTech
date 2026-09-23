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

> Le bouton de paiement des frais d'inscription, annoncé ici comme désactivé,
> a été branché en **phase 3**.

---

## Phase 3 — Passerelle de paiement simulée ✅

- [x] Contrat `PaymentGateway` (`initiate`, `verifyCallback`, `getStatus`, `refund`)
- [x] `FakeMobileMoneyGateway`, liée par `PAYMENT_GATEWAY` ; une passerelle inconnue échoue bruyamment au démarrage
- [x] Page de paiement de test : bandeau « Environnement de test », montant, opérateur, numéro, **Confirmer / Refuser / Laisser expirer**
- [x] Callback signé HMAC-SHA256 (horodatage inclus dans la signature), envoyé par un job en file d'attente avec latence configurable
- [x] Webhook : signature vérifiée **avant toute lecture du payload**, exempté de CSRF, payload brut journalisé
- [x] **Idempotence garantie par la base** : table `payment_callbacks`, `event_id` unique
- [x] Montant du callback **comparé**, jamais cru
- [x] Commande `agritech:payment:simulate {reference} {issue} [--duplicate] [--now]`
- [x] Commande `agritech:payments:reconcile`, planifiée toutes les 5 minutes
- [x] Deux numéros à issue forcée, documentés dans le README
- [x] Branchement sur les frais d'inscription agriculteur → `pending_validation` (RG02) + notification admin
- [x] Effet métier manquant = exception explicite, jamais un silence
- [x] Écran d'attente qui **affiche** sans rien accorder (RG06)
- [x] Rate limiting sur les écrans de paiement
- [x] 36 tests ajoutés

**Acceptation :** parcours complet d'inscription agriculteur jusqu'à
`pending_validation`, vérifié **dans l'application réelle avec `queue:work`** ·
succès, échec, expiration et callback dupliqué testés · signature forgée ou
absente → 403, aucun enregistrement, aucun effet, rejet journalisé · **aucun
paiement ne devient `succeeded` par une redirection navigateur** · réconciliation
testée · `composer test` intégralement propre (Pint, Larastan niveau 7, 271 tests).

> **Reporté aux phases suivantes :** les effets métier de `order` (phase 6),
> `training` (phase 7) et `subscription` (phase 8). Le registre lève une
> exception explicite tant qu'ils ne sont pas écrits.

---

## Phase 4 — Administration ✅

- [x] Tableau de bord admin : comptes à valider, publications en attente, paiements sans réponse, encaissé du jour
- [x] Approbation / refus des agriculteurs, **motif obligatoire** envoyé par e-mail et affiché sur l'écran de statut
- [x] Suspension, réintégration, **suppression logique avec anonymisation**
- [x] Écran des utilisateurs : recherche, filtres rôle et statut
- [x] Gestion des privilèges par administrateur
- [x] Écran des paramètres, validation selon le type
- [x] Journal d'audit : filtres, détail avant/après
- [x] Un Gate par privilège, déclaré depuis `Privilege::catalogue()`
- [x] Policies `UserPolicy`, `SettingPolicy`, `AuditLogPolicy`
- [x] Garde-fous : pas d'auto-suspension, pas d'auto-suppression, pas d'auto-modification de ses privilèges, dernier administrateur protégé
- [x] Notifications `FarmerApproved` et `FarmerRejected`
- [x] Navigation admin adaptée aux privilèges détenus
- [x] 44 tests ajoutés

**Acceptation :** **RG07** — chaque action testée avec et sans son privilège,
et vérifiée dans l'application réelle avec un administrateur volontairement
limité · **RG08** — compte anonymisé (`email` et `phone` à `NULL`), commandes
et paiements intacts, connexion impossible, adresse libérée pour une nouvelle
inscription · **RG11** — chaque action sensible produit une entrée avec
avant/après, et la suppression n'y recopie pas la donnée qu'elle efface ·
parcours complet inscription → paiement → validation → espace agriculteur
ouvert · `composer test` intégralement propre (Pint, Larastan niveau 7, 315 tests).

> L'écran de modération, annoncé ici comme non livré, a été ajouté en
> **phase 5**, pour les produits comme pour les formations.

---

## Phase 5 — Catalogue et publication ✅

- [x] CRUD produits côté agriculteur : liste, création, modification, soumission, archivage
- [x] Upload d'images : **type MIME réel** vérifié, taille, dimensions, nombre, **renommage en ULID**
- [x] Images sur **disque privé servies par un contrôleur** — aucune dépendance à `storage:link`
- [x] Circuit de modération (RG09) piloté par le paramètre `prior_moderation_enabled`
- [x] Écran de modération admin : produits **et** formations, refus avec motif obligatoire
- [x] CRUD catégories + nouveau privilège `categories.manage`
- [x] Catalogue public : recherche, filtres catégorie et région, tri, pagination
- [x] Fiche produit publique
- [x] Layout `public` et page d'accueil AgriTech
- [x] `SlugGenerator` : noms identiques → slugs distincts, slug stable à la modification
- [x] Scope `visibleToPublic()` : un agriculteur suspendu ou supprimé cesse de vendre
- [x] Images de démonstration générées avec GD
- [x] 52 tests ajoutés

**Acceptation :** **RG01** — un agriculteur non actif ne peut rien publier,
vérifié via l'écran **et** via le service · **RG09** — passage par `in_review`,
ou publication directe si la modération a priori est désactivée · produit
d'agriculteur suspendu absent du catalogue et fiche en **404** · fichier PHP
nommé `.jpg` refusé · trois produits de même nom → trois slugs · grille sans
N+1, vérifié en comptant les requêtes · `composer test` intégralement propre
(Pint, Larastan niveau 7, 367 tests à l'issue de cette phase).

---

## Phase 6 — Panier et commandes ✅

- [x] Panier multi-agriculteurs **en base**, lignes groupées par agriculteur
- [x] Ligne indisponible signalée et exclue du total, jamais supprimée en silence
- [x] Commande + une sous-commande par agriculteur, références `-A`, `-B`…
- [x] Prix unitaire **et** taux de commission figés à la création (RG03)
- [x] Paiement simulé de la commande, montant lu sur la commande et jamais reçu
- [x] Un second clic sur « Payer » réutilise le paiement en cours
- [x] Décrément du stock sous `lockForUpdate`, dans la transaction du paiement (RG04)
- [x] Stock disparu ou commande déjà annulée → annulation totale + remboursement
- [x] Suivi côté client (paiement, préparation, livraison) et côté agriculteur
- [x] Transitions `payée → en préparation → livrée`, avec remontée au niveau commande
- [x] `agritech:orders:cancel-expired` + planification toutes les cinq minutes
- [x] Une commande dont le paiement est en cours de vérification est épargnée
- [x] Suite de tests `Concurrency` : vrais sous-processus, vraies connexions
- [x] 59 tests ajoutés

**Acceptation :** **RG03** — quantité supérieure au stock refusée, rien
d'écrit · **RG04** — stock déplacé uniquement à la confirmation, **test de
concurrence à deux processus** vérifié comme discriminant (il échoue si l'on
retire `lockForUpdate`) · **RG06** — ni la page de retour ni un montant reçu ne
paient une commande · commission figée malgré un changement de paramètre ·
callback rejoué sans double décrément · `composer test` intégralement propre
(Pint, Larastan niveau 7, 426 tests).

---

## Phase 7 — Formations ✅

- [x] CRUD formations côté agriculteur : liste, création, modification, soumission, archivage
- [x] Upload des contenus (vidéo MP4/WebM, PDF) : type MIME réel vérifié, renommage ULID, disque privé
- [x] Achat d'une formation : montant lu sur la fiche, jamais reçu du formulaire ; second clic renvoyé vers le paiement en cours
- [x] Effet métier `TrainingOutcome` : achat enregistré dans la transaction qui confirme le paiement
- [x] Accès au contenu par contrôleur vérifiant l'entitlement (RG05) — jamais d'URL publique directe
- [x] Pages publiques : liste avec recherche et filtre par format, fiche avec modules (titres visibles, fichiers verrouillés)
- [x] Espace client « Mes formations » : achats + formations ouvertes par l'abonnement
- [x] Continuation de paiement : la page « vérification » renvoie vers la formation payée
- [x] 43 tests ajoutés

**Acceptation :** **RG05** — fichier servi uniquement à l'acheteur ou à l'abonné actif
(formation incluse), visiteur en redirection, étranger en 403, terme expiré en
403, fichier disparu en 404, chemin jamais exposé dans les vues · **RG06** —
aucun accès accordé avant le callback vérifié, callback rejoué absorbé par
l'unicité de l'achat · **RG09** — passage par `in_review`, ou publication
directe si la modération a priori est désactivée · `composer test` intégralement
propre (Pint, Larastan niveau 7, 469 tests à l'issue de cette phase).

---

## Phase 8 — Abonnements ✅

- [x] Écran client : plan en cours, plans disponibles, souscription avec paiement simulé
- [x] Montant lu sur le plan, jamais reçu du formulaire ; second clic renvoyé vers le paiement en vol
- [x] Pas de chevauchement : un terme en cours bloque toute nouvelle souscription (décision DECISIONS.md)
- [x] Effet métier `SubscriptionOutcome` : activation dans la transaction qui confirme, horloge démarrant à la confirmation
- [x] `agritech:subscriptions:expire` planifiée toutes les cinq minutes
- [x] Accès aux formations incluses piloté par RG05 (statut + terme)
- [x] Registre des effets métier complété : `match` exhaustif, tout purpose a son handler
- [x] 16 tests ajoutés

**Acceptation :** le terme ne s'ouvre qu'à la confirmation vérifiée · abonnement
actif ouvre les formations incluses, terme expiré les ferme · renouvellement
possible dès que le terme est passé · écran refusé aux non-clients ·
`composer test` intégralement propre (Pint, Larastan niveau 7, 469 tests).

---

## Phase 9 — Messagerie et notifications ✅

- [x] Conversations client ↔ agriculteur, un fil par paire, créé à la première demande
- [x] Bouton « Contacter l'agriculteur » sur la fiche produit (service résout le fil depuis le produit)
- [x] Envoi vérifié : appartenance au fil, longueur 1–5000, `last_message_at` mis à jour
- [x] Compteur de messages non lus par fil et global (badge de navigation), remis à zéro à l'ouverture
- [x] Rafraîchissement par polling Livewire (5 s), sans WebSocket — contrainte 100 % local
- [x] Notification `NewMessage` : base (badge) + e-mail (log local)
- [x] 14 tests ajoutés

**Acceptation :** un fil par paire, réutilisé · l'ouverture du fil ne marque lus
que les messages de l'autre partie · compteurs exacts de part et d'autre ·
intrus en 403 (écran et service) · notification envoyée au destinataire actif.

---

## Phase 10 — Tableaux de bord ✅

- [x] Tableau de bord agriculteur : chiffre d'affaires payé, commission plateforme, à préparer, catalogue publié, messages non lus, files de sous-commandes en attente
- [x] Les sous-commandes annulées ou impayées n'entrent dans aucun total : une sous-commande annulée n'a jamais été du travail
- [x] Tableau de bord administrateur : comptes à valider, publications à modérer, paiements en vol, commandes à payer / livrées, encaissé du jour et total, abonnements actifs
- [x] Chaque tuile = une requête agrégée ; les dashboards se protègent eux-mêmes (rôle vérifié au montage)
- [x] 6 tests ajoutés

**Acceptation :** les totaux ne comptent que le travail réellement payé ·
l'écran agriculteur refuse client et suspendu, l'écran admin refuse tout non-admin.

---

## Phase 11 — Finalisation locale ✅

- [x] Revue de sécurité : toute écriture sensible passe par les services, autorisations serveur (policies + middleware + vérifications au montage des composants)
- [x] Performances : pagination partout, eager loading systématique, comptage de requêtes testé sur les grilles
- [x] Design system Stitch appliqué (palette, polices Plus Jakarta Sans / Inter, rayons, ombres teintées) — voir DECISIONS.md
- [x] Suite de tests complète : 491 tests (unitaires, fonctionnels, concurrence à deux processus)
- [x] `README.md` d'installation pas à pas sous Windows, à partir de zéro
- [x] Liste des comptes de démonstration (§ 4 du README)
- [x] Guide de test manuel couvrant chaque parcours, paiements réussis, échoués et expirés inclus (§ 5 et § 8 du README)
- [x] Commande `agritech:reset-demo`

**Acceptation :** `composer test` intégralement propre — Pint (267 fichiers),
Larastan niveau 7, **491 tests**. Une personne qui découvre le projet peut
l'installer et tester tous les parcours en suivant uniquement le `README.md`.

> **Environnement d'exécution des tests :** la suite a été exécutée sous Linux
> (PHP 8.4.25, MariaDB 10.6). Les variables exportées par certains environnements
> de développement écrasaient celles de `phpunit.xml` (voir DECISIONS.md) ; le
> correctif (`force="true"` + promotion `$_ENV` → `$_SERVER`) rend la suite
> insensible à l'environnement du shell, sous Windows comme sous Linux.

---

## Refonte du design — reproduction des maquettes Stitch

Le dossier `stitch_conception_design_application/` contient 35 écrans. La
consigne retenue est la **reproduction stricte** : jetons, typographie,
icônes Material Symbols et marquage repris des maquettes. Les écrans
client et agriculteur n'existent qu'en version mobile ; la version large
est extrapolée des maquettes `web_dashboard` de l'administration.

Un principe éditorial traverse tous les lots : **ne jamais afficher une
promesse que le serveur ne tient pas.** Les maquettes annoncent des notes
d'avis, des remises sur volume, des délais de livraison, des factures PDF
et un numéro d'assistance ; rien de tout cela n'existe. L'emplacement
garde sa forme, le texte dit ce que la plateforme fait vraiment.

- [x] **Lot 0** — fondations : jetons `@theme` dérivés des maquettes, polices
      auto-hébergées, Material Symbols, composants `x-icon`, `x-button`,
      `x-card`, `x-badge`, `x-chip`, `x-field`, `x-nav-item`, coquilles
      mobile et large
- [x] **Lot 1** — accueil, catalogue produits, fiche produit
- [x] **Lot 2** — panier, choix du moyen de paiement, passerelle de test,
      paiement en attente / réussi / échoué, détail de commande,
      mes commandes
- [x] **Lot 3** — formations : catalogue, fiche, lecteur, mes formations,
      abonnements
- [x] **Lot 4** — messagerie, notifications, mon compte
- [x] **Lot 5** — connexion, choix d'inscription, inscriptions client et
      agriculteur, compte en attente de validation
- [x] **Lot 6** — espace agriculteur : tableau de bord, mes produits,
      formulaire produit, mes formations
- [x] **Lot 7** — administration : tableau de bord, utilisateurs,
      agriculteurs à valider, modération, privilèges, journal d'audit,
      paramètres (et l'écran des catégories, qui n'a pas de maquette)

**Acceptation d'un lot :** écrans lisibles à 360 px et en version large,
`composer test` propre (Pint, Larastan niveau 7, suite complète), captures
prises dans un vrai navigateur.

**Les 35 écrans sont reproduits.** Ce qui reste à faire, le cas échéant :
repasser sur les écarts consignés si une donnée manquante venait à exister
(progression de lecture d'une formation, avis, suivi de livraison, exports).
