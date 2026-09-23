<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentPurpose;
use App\Enums\PaymentStatus;
use App\Enums\ProductUnit;
use App\Enums\PublicationStatus;
use App\Enums\SubOrderStatus;
use App\Enums\SubscriptionStatus;
use App\Enums\TrainingContentType;
use App\Enums\TrainingFormat;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\Conversation;
use App\Models\FarmerProfile;
use App\Models\Message;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\Setting;
use App\Models\SubOrder;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\Training;
use App\Models\TrainingContent;
use App\Models\TrainingPurchase;
use App\Models\User;
use App\Notifications\FarmerApproved;
use App\Notifications\FarmerAwaitingValidation;
use App\Notifications\NewMessage;
use App\Notifications\OrderCancelled;
use App\Notifications\OrderPaid;
use App\Notifications\PublicationApproved;
use App\Notifications\SubOrderReceived;
use App\Support\Money;
use App\Support\PlaceholderImage;
use App\Support\PlaceholderPdf;
use App\Support\Quantity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * A believable walkthrough data set: accounts at every stage, a catalogue,
 * orders in each state, a subscription and a conversation.
 *
 * Credentials are fixed and listed in the README so the manual test guide can
 * be followed step by step. This is local demo data and never ships anywhere.
 */
class DemoSeeder extends Seeder
{
    public const string PASSWORD = 'password';

    /**
     * The modules of each demonstration training, in reading order.
     *
     * @var array<string, array<int, string>>
     */
    private const array MODULES = [
        'compostage' => [
            'Pourquoi composter : ce que le sol y gagne',
            'Monter un tas de compost en andain',
            'Retourner, arroser, surveiller la température',
            'Reconnaître un compost mûr et l\'épandre',
        ],
        'irrigation' => [
            'Mesurer les besoins en eau de sa parcelle',
            'Choisir tuyaux, goutteurs et filtration',
            'Poser le réseau et régler la pression',
            'Entretenir le système en saison sèche',
        ],
        'cacao' => [
            'Reconnaître les variétés et leurs exigences',
            'Tailler et gérer l\'ombrage',
            'Lutter contre la pourriture brune',
            'Récolter, écabosser, fermenter',
            'Sécher et trier avant la vente',
        ],
        'conservation' => [
            'Sécher correctement avant le stockage',
            'Choisir sacs, greniers et palettes',
            'Prévenir charançons et moisissures',
        ],
    ];

    private bool $gdWarned = false;

    public function run(): void
    {
        $commissionRate = Setting::query()
            ->where('key', Setting::PLATFORM_COMMISSION_RATE)
            ->first()?->integerValue() ?? 5;

        $categories = Category::query()->get()->keyBy('slug');
        $admin = User::query()->where('email', SuperAdminSeeder::EMAIL)->firstOrFail();

        // --- Farmers, one per account stage -----------------------------------

        $activeFarmer = $this->createFarmer(
            'agriculteur@agritech.local',
            'Awono',
            'Bernard',
            '+237670000001',
            UserStatus::Active,
            'Ferme du Mbam',
            'Centre',
            'Obala',
        );
        $activeFarmer->farmerProfile?->markValidatedBy($admin);

        $secondFarmer = $this->createFarmer(
            'agricultrice@agritech.local',
            'Ngo Bell',
            'Solange',
            '+237690000002',
            UserStatus::Active,
            'Coopérative des Hauts Plateaux',
            'Ouest',
            'Dschang',
        );
        $secondFarmer->farmerProfile?->markValidatedBy($admin);

        $this->createFarmer(
            'agriculteur-attente@agritech.local',
            'Tchoupo',
            'Marcel',
            '+237680000003',
            UserStatus::PendingValidation,
            'Plantation de Kribi',
            'Sud',
            'Kribi',
        );

        $this->createFarmer(
            'agriculteur-impaye@agritech.local',
            'Fotso',
            'Rachelle',
            '+237670000004',
            UserStatus::PendingPayment,
            'Jardins de Bafoussam',
            'Ouest',
            'Bafoussam',
        );

        $rejectedFarmer = $this->createFarmer(
            'agriculteur-refuse@agritech.local',
            'Mbarga',
            'Joseph',
            '+237690000005',
            UserStatus::Rejected,
            'Exploitation Sanaga',
            'Centre',
            'Mbalmayo',
        );
        $rejectedFarmer->farmerProfile?->markRejectedBy(
            $admin,
            "Le nom de l'exploitation ne correspond pas aux justificatifs fournis.",
        );

        // --- Clients -----------------------------------------------------------

        $client = $this->createClient('client@agritech.local', 'Etoundi', 'Clarisse', '+237650000001');
        $secondClient = $this->createClient('client2@agritech.local', 'Njoya', 'Ibrahim', '+237650000002');

        // --- Catalogue ---------------------------------------------------------

        $products = $this->createProducts($activeFarmer, $secondFarmer, $categories->all());
        $trainings = $this->createTrainings($activeFarmer, $secondFarmer);

        // Accounts and catalogue above are written with updateOrCreate, so they
        // survive a second run. The transactions below are not idempotent by
        // nature — an order is an event, not a record to reconcile — so they
        // are created once and skipped afterwards.
        if (Order::query()->exists()) {
            return;
        }

        // --- Orders ------------------------------------------------------------

        $paidOrder = $this->createOrder(
            $client,
            // Deliberately spans two farmers, so the sub-order split is
            // visible in the demo data rather than only in the tests.
            [[$products['plantain'], '25'], [$products['tomate'], '12.5'], [$products['miel'], '2']],
            OrderStatus::Paid,
            $commissionRate,
        );
        $this->createPayment($client, $paidOrder->total_amount, PaymentPurpose::Order, $paidOrder, succeeded: true);

        $pendingOrder = $this->createOrder(
            $secondClient,
            [[$products['cafe'], '3']],
            OrderStatus::PendingPayment,
            $commissionRate,
        );
        $this->createPayment($secondClient, $pendingOrder->total_amount, PaymentPurpose::Order, $pendingOrder, succeeded: false);

        $this->createOrder(
            $client,
            [[$products['manioc'], '40']],
            OrderStatus::Cancelled,
            $commissionRate,
        );

        // --- Cart in progress ---------------------------------------------------

        // So the cart screen has something to show on a fresh install, and the
        // checkout can be walked through end to end without hunting for a
        // product first.
        $cart = Cart::create(['client_id' => $secondClient->id]);

        foreach ([['tomate', '3'], ['miel', '1']] as [$key, $quantity]) {
            CartItem::create([
                'cart_id' => $cart->id,
                'product_id' => $products[$key]->id,
                'quantity' => Quantity::fromString($quantity),
            ]);
        }

        // --- Trainings bought and subscribed to --------------------------------

        TrainingPurchase::create([
            'client_id' => $client->id,
            'training_id' => $trainings['compostage']->id,
            'amount' => $trainings['compostage']->price,
            'purchased_at' => now()->subDays(3),
        ]);
        $this->createPayment(
            $client,
            $trainings['compostage']->price,
            PaymentPurpose::Training,
            $trainings['compostage'],
            succeeded: true,
        );

        $plan = SubscriptionPlan::query()->where('slug', 'trimestriel')->firstOrFail();
        $subscription = Subscription::create([
            'client_id' => $secondClient->id,
            'plan_id' => $plan->id,
            'status' => SubscriptionStatus::PendingPayment,
        ]);
        $subscription->activate(now()->subDays(5));
        $this->createPayment($secondClient, $plan->price, PaymentPurpose::Subscription, $subscription, succeeded: true);

        // --- Messaging ---------------------------------------------------------

        $conversation = Conversation::create([
            'client_id' => $client->id,
            'farmer_id' => $activeFarmer->id,
            'last_message_at' => now()->subHours(2),
        ]);

        Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $client->id,
            'content' => 'Bonjour, vos régimes de plantain sont-ils disponibles cette semaine ?',
            'read_at' => now()->subHours(3),
            'created_at' => now()->subHours(4),
            'updated_at' => now()->subHours(4),
        ]);

        $reply = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $activeFarmer->id,
            'content' => 'Bonjour, oui, la récolte de jeudi sera disponible dès vendredi matin.',
            'read_at' => null,
            'created_at' => now()->subHours(2),
            'updated_at' => now()->subHours(2),
        ]);

        // --- Notifications -----------------------------------------------------

        $this->createNotifications($admin, $activeFarmer, $client, $paidOrder, $reply, $products['plantain']);
    }

    /**
     * Fill the notifications screen with rows the services would really have
     * written.
     *
     * The demo builds its orders and messages directly rather than through
     * the services, so none of the notifications those services send ever
     * fired. They are sent here, through the very same notification classes —
     * writing the rows by hand would let the payload drift from what the
     * application actually stores.
     *
     * Only the database channel is used: mailing the demo accounts at seeding
     * time would fill the log with nine messages nobody reads.
     */
    private function createNotifications(
        User $admin,
        User $farmer,
        User $client,
        Order $paidOrder,
        Message $reply,
        Product $product,
    ): void {
        $pendingFarmer = User::query()->where('email', 'agriculteur-attente@agritech.local')->first();
        $cancelled = Order::query()->where('status', OrderStatus::Cancelled)->first();
        $subOrder = $paidOrder->subOrders()->where('farmer_id', $farmer->id)->first();

        $sent = [
            [$client, new OrderPaid($paidOrder), 2],
            [$client, new NewMessage($reply), 2],
            [$farmer, new PublicationApproved($product), 26],
        ];

        if ($cancelled instanceof Order) {
            $sent[] = [$client, new OrderCancelled($cancelled, 'Paiement non confirmé dans le délai imparti.', false), 50];
        }

        if ($subOrder instanceof SubOrder) {
            $sent[] = [$farmer, new SubOrderReceived($subOrder), 3];
        }

        if ($pendingFarmer instanceof User) {
            $sent[] = [$admin, new FarmerAwaitingValidation($pendingFarmer), 27];
        }

        $sent[] = [$farmer, new FarmerApproved, 74];

        foreach ($sent as [$notifiable, $notification, $hoursAgo]) {
            Notification::sendNow($notifiable, $notification, ['database']);

            // `sendNow` stamps the row with the current time; the demo wants
            // them spread over three days so the screen's day grouping —
            // « Aujourd'hui », « Hier », puis la date — has something to group.
            $notifiable->notifications()->latest()->limit(1)->update([
                'created_at' => now()->subHours($hoursAgo),
                'updated_at' => now()->subHours($hoursAgo),
            ]);
        }

        // Two rows left unread, so the badge and the "Tout lire" button both
        // have something to do on a fresh install.
        $client->notifications()->latest()->skip(2)->take(10)->get()
            ->each(fn (DatabaseNotification $row) => $row->markAsRead());
        $farmer->notifications()->latest()->skip(1)->take(10)->get()
            ->each(fn (DatabaseNotification $row) => $row->markAsRead());
    }

    private function createFarmer(
        string $email,
        string $lastName,
        string $firstName,
        string $phone,
        UserStatus $status,
        string $farmName,
        string $region,
        string $city,
    ): User {
        $farmer = User::updateOrCreate(
            ['email' => $email],
            [
                'first_name' => $firstName,
                'last_name' => $lastName,
                'phone' => $phone,
                'password' => self::PASSWORD,
                'role' => UserRole::Farmer,
                'status' => $status,
                'email_verified_at' => now(),
            ],
        );

        FarmerProfile::updateOrCreate(
            ['user_id' => $farmer->id],
            [
                'farm_name' => $farmName,
                'region' => $region,
                'city' => $city,
                'description' => 'Exploitation familiale installée à '.$city.'.',
            ],
        );

        return $farmer->fresh() ?? $farmer;
    }

    private function createClient(string $email, string $lastName, string $firstName, string $phone): User
    {
        return User::updateOrCreate(
            ['email' => $email],
            [
                'first_name' => $firstName,
                'last_name' => $lastName,
                'phone' => $phone,
                'password' => self::PASSWORD,
                'role' => UserRole::Client,
                'status' => UserStatus::Active,
                'email_verified_at' => now(),
            ],
        );
    }

    /**
     * @param  array<string, Category>  $categories
     * @return array<string, Product>
     */
    private function createProducts(User $firstFarmer, User $secondFarmer, array $categories): array
    {
        $definitions = [
            'plantain' => [$firstFarmer, 'tubercules-et-racines', 'Régime de plantain', 2500, ProductUnit::Bunch, '120'],
            'manioc' => [$firstFarmer, 'tubercules-et-racines', 'Manioc frais', 400, ProductUnit::Kilogram, '850.5'],
            'tomate' => [$firstFarmer, 'legumes', 'Tomate fraîche', 800, ProductUnit::Kilogram, '230'],
            'cafe' => [$secondFarmer, 'cultures-de-rente', 'Café arabica en grains', 4500, ProductUnit::Kilogram, '75.25'],
            'miel' => [$secondFarmer, 'produits-transformes', "Miel d'Oku", 6000, ProductUnit::Litre, '40'],
            'poulet' => [$secondFarmer, 'elevage-et-volaille', 'Poulet de chair', 3500, ProductUnit::Piece, '60'],
        ];

        $products = [];

        foreach ($definitions as $key => [$farmer, $categorySlug, $name, $price, $unit, $stock]) {
            $products[$key] = Product::updateOrCreate(
                ['slug' => Str::slug($name)],
                [
                    'farmer_id' => $farmer->id,
                    'category_id' => $categories[$categorySlug]->id,
                    'name' => $name,
                    'description' => $name.' produit et récolté localement, vendu directement par le producteur.',
                    'unit_price' => Money::fromInteger($price),
                    'unit' => $unit,
                    'stock_quantity' => Quantity::fromString($stock),
                    'status' => PublicationStatus::Published,
                ],
            );
        }

        // Scènes thématiques : chaque produit reçoit une illustration qui le
        // représente, pas un simple aplat de couleur.
        $scenes = [
            'plantain' => 'plantain',
            'manioc' => 'manioc',
            'tomate' => 'tomate',
            'cafe' => 'cafe',
            'miel' => 'miel',
            'poulet' => 'poulet',
        ];

        foreach ($products as $key => $product) {
            $this->attachPlaceholderImage($product, $scenes[$key]);
        }

        // One product still waiting on moderation, so the admin screen has
        // something to act on out of the box.
        Product::updateOrCreate(
            ['slug' => 'ananas-de-bafia'],
            [
                'farmer_id' => $firstFarmer->id,
                'category_id' => $categories['fruits']->id,
                'name' => 'Ananas de Bafia',
                'description' => 'Ananas sucré cultivé sans engrais chimique.',
                'unit_price' => Money::fromInteger(1000),
                'unit' => ProductUnit::Piece,
                'stock_quantity' => Quantity::fromInteger(95),
                'status' => PublicationStatus::InReview,
            ],
        );

        return $products;
    }

    /**
     * Give a product a small gallery of generated illustrations, so the
     * catalogue and the product page look like a market rather than a grid
     * of empty boxes.
     *
     * The first image is the product's themed scene; the two extras are the
     * generic field scene in varying canvas tints, which the product page
     * shows as secondary gallery thumbnails.
     *
     * Skipped when the product already has images, which keeps the seeder
     * rerunnable.
     */
    private function attachPlaceholderImage(Product $product, string $scene = 'generic'): void
    {
        if ($product->images()->exists()) {
            return;
        }

        // Une vraie photographie vaut mieux qu'un dessin : si le dépôt en
        // contient pour ce produit, on les prend. Le dessin GD reste le repli.
        if ($this->attachPhotographs($product)) {
            return;
        }

        // Without GD the catalogue still works — the cards just fall back to
        // their empty-image placeholder. Seeding must never hang on a demo
        // nicety; the extension is listed as required in the README.
        if (! PlaceholderImage::isSupported()) {
            if (! $this->gdWarned) {
                $this->command->warn(
                    "L'extension PHP GD n'est pas activée : les images de démonstration sont ignorées.\n".
                    '   Décommentez "extension=gd" dans php.ini, redémarrez, puis relancez php artisan migrate:fresh --seed.',
                );
                $this->gdWarned = true;
            }

            return;
        }

        $disk = Storage::disk((string) config('catalog.images.disk', 'local'));

        // One themed scene plus two tinted companions: the detail page gets
        // a real gallery to flip through.
        $tints = [
            [34, 110, 62],
            [176, 122, 32],
            [64, 120, 96],
        ];

        foreach ($tints as $index => $tint) {
            $sceneKey = $index === 0 ? $scene : 'generic';

            $path = 'products/'.$product->id.'/'.Str::ulid()->toString().'.png';

            $disk->put($path, PlaceholderImage::png($sceneKey, $tint));

            ProductImage::create([
                'product_id' => $product->id,
                'path' => $path,
                'position' => $index + 1,
            ]);
        }
    }

    /**
     * Copy the repository's photographs for this product, if it has any.
     *
     * The files live in `database/seeders/photos/products`, are named after
     * the product slug, and carry their licence in CREDITS.md next to them.
     * They are committed rather than downloaded at seed time: seeding has to
     * work without a network, like the rest of the application.
     *
     * @return bool true when photographs were attached
     */
    private function attachPhotographs(Product $product): bool
    {
        $files = $this->photographsFor('products', $product->slug);

        if ($files === []) {
            return false;
        }

        $disk = Storage::disk((string) config('catalog.images.disk', 'local'));

        foreach ($files as $index => $file) {
            $path = 'products/'.$product->id.'/'.Str::ulid()->toString().'.jpg';

            $disk->put($path, (string) file_get_contents($file));

            ProductImage::create([
                'product_id' => $product->id,
                'path' => $path,
                'position' => $index + 1,
            ]);
        }

        return true;
    }

    /**
     * The repository's photographs for a slug, in display order.
     *
     * Accepts both `slug.jpg` and `slug-1.jpg`, `slug-2.jpg`…
     *
     * @return array<int, string> absolute paths
     */
    private function photographsFor(string $kind, string $slug): array
    {
        $directory = database_path('seeders/photos/'.$kind);

        $files = array_merge(
            glob($directory.'/'.$slug.'.jpg') ?: [],
            glob($directory.'/'.$slug.'-*.jpg') ?: [],
        );

        sort($files);

        return $files;
    }

    /**
     * Give a training an illustrated 16:9 cover, drawn on the private disk
     * and streamed by the cover controller like product images are. There is
     * no cover column on the model: the file is keyed by the training slug,
     * and the public screens fall back to the gradient hero when it is
     * absent (for instance after a seed without GD).
     *
     * Skipped when the cover already exists, which keeps the seeder
     * rerunnable.
     */
    private function attachTrainingCover(Training $training, string $scene): void
    {
        $disk = Storage::disk((string) config('catalog.images.disk', 'local'));
        $photographs = $this->photographsFor('trainings', $training->slug);

        // La photographie l'emporte, y compris sur un dessin laissé par un
        // amorçage précédent : `migrate:fresh` vide la base, pas le disque.
        if ($photographs !== []) {
            $disk->put(
                'training-covers/'.$training->slug.'.jpg',
                (string) file_get_contents($photographs[0]),
            );

            $disk->delete('training-covers/'.$training->slug.'.png');

            return;
        }

        if ($training->hasCover() || ! PlaceholderImage::isSupported()) {
            return;
        }

        $disk->put('training-covers/'.$training->slug.'.png', PlaceholderImage::cover($scene));
    }

    /**
     * @return array<string, Training>
     */
    private function createTrainings(User $firstFarmer, User $secondFarmer): array
    {
        // Toutes au format document. Le format annonce ce que l'acheteur
        // recevra, et une démonstration ne peut fabriquer de vidéo hors
        // ligne : aucun encodeur n'est une dépendance du projet. Annoncer
        // « Vidéo » sans vidéo derrière serait précisément la promesse que
        // ce projet s'interdit. Voir DECISIONS.md.
        $definitions = [
            'compostage' => [$firstFarmer, 'Composter ses déchets agricoles', 7500, TrainingFormat::Pdf, false],
            'irrigation' => [$firstFarmer, 'Irrigation goutte à goutte à petit budget', 5000, TrainingFormat::Pdf, true],
            'cacao' => [$secondFarmer, 'Entretenir une cacaoyère productive', 12000, TrainingFormat::Pdf, true],
            'conservation' => [$secondFarmer, 'Conserver les récoltes après la moisson', 4000, TrainingFormat::Pdf, true],
        ];

        $trainings = [];

        foreach ($definitions as $key => [$farmer, $title, $price, $format, $included]) {
            $trainings[$key] = Training::updateOrCreate(
                ['slug' => Str::slug($title)],
                [
                    'farmer_id' => $farmer->id,
                    'title' => $title,
                    'description' => 'Formation pratique : '.$title.'.',
                    'price' => Money::fromInteger($price),
                    'format' => $format,
                    'included_in_subscription' => $included,
                    'status' => PublicationStatus::Published,
                ],
            );
        }

        // Couvertures illustrées 16:9, une scène par formation.
        $coverScenes = [
            'compostage' => 'formation-compostage',
            'irrigation' => 'formation-irrigation',
            'cacao' => 'formation-cacao',
            'conservation' => 'formation-conservation',
        ];

        foreach ($trainings as $key => $training) {
            $this->attachTrainingCover($training, $coverScenes[$key]);
            $this->attachTrainingModules($training, self::MODULES[$key]);
        }

        return $trainings;
    }

    /**
     * Write the modules of a training to the private disk and record them.
     *
     * Without them the reader screen has nothing to read, and the entitlement
     * check in TrainingContentController is never exercised by the demo. The
     * files are generated, not committed: a PDF built from pure PHP needs no
     * extension, no binary and no network.
     *
     * @param  array<int, string>  $titles
     */
    private function attachTrainingModules(Training $training, array $titles): void
    {
        $disk = Storage::disk((string) config('trainings.contents.disk', 'local'));
        $directory = trim((string) config('trainings.contents.directory', 'trainings'), '/').'/'.$training->id;

        foreach ($titles as $index => $title) {
            $position = $index + 1;
            $path = $directory.'/module-'.$position.'.pdf';

            $disk->put($path, PlaceholderPdf::render($title, [
                $training->title.' — module '.$position.' sur '.count($titles).'.',
                'Ce document tient la place du support que l\'agriculteur téléverse. '
                    .'Il est généré localement par le jeu de démonstration ; son contenu '
                    .'n\'a pas de valeur agronomique.',
                'Le fichier vit sur un disque privé et n\'est servi qu\'aux clients qui y ont droit : '
                    .'achat de la formation, ou abonnement actif l\'incluant (règle RG05).',
            ]));

            TrainingContent::updateOrCreate(
                ['training_id' => $training->id, 'position' => $position],
                ['title' => $title, 'type' => TrainingContentType::Pdf, 'path' => $path],
            );
        }
    }

    /**
     * Build an order with its per-farmer sub-orders and lines, keeping every
     * total consistent with the lines beneath it.
     *
     * @param  array<int, array{0: Product, 1: string}>  $lines
     */
    private function createOrder(User $client, array $lines, OrderStatus $status, int $commissionRate): Order
    {
        $order = Order::create([
            'reference' => Order::nextReference(),
            'client_id' => $client->id,
            'total_amount' => Money::zero(),
            'status' => OrderStatus::PendingPayment,
            'expires_at' => $status === OrderStatus::PendingPayment ? now()->addMinutes(30) : null,
        ]);

        /** @var array<int, array<int, array{0: Product, 1: string}>> $byFarmer */
        $byFarmer = [];

        foreach ($lines as $line) {
            $byFarmer[$line[0]->farmer_id][] = $line;
        }

        $index = 0;
        $total = Money::zero();

        foreach ($byFarmer as $farmerId => $farmerLines) {
            $subtotal = Money::zero();

            $subOrder = SubOrder::create([
                'order_id' => $order->id,
                'farmer_id' => $farmerId,
                'reference' => SubOrder::referenceFor($order, $index),
                'subtotal_amount' => Money::zero(),
                'commission_rate_snapshot' => $commissionRate,
                'commission_amount' => Money::zero(),
                'status' => SubOrderStatus::PendingPayment,
            ]);

            foreach ($farmerLines as [$product, $rawQuantity]) {
                $quantity = Quantity::fromString($rawQuantity);
                $lineTotal = $product->unit_price->multipliedByQuantity($quantity);

                OrderItem::create([
                    'sub_order_id' => $subOrder->id,
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                    'unit_price_snapshot' => $product->unit_price,
                    'line_total' => $lineTotal,
                ]);

                $subtotal = $subtotal->plus($lineTotal);
            }

            $subOrder->forceFill([
                'subtotal_amount' => $subtotal->amount,
                'commission_amount' => $subtotal->percentage($commissionRate)->amount,
            ])->save();

            $total = $total->plus($subtotal);
            $index++;
        }

        $order->forceFill(['total_amount' => $total->amount])->save();

        if ($status !== OrderStatus::PendingPayment) {
            $this->applyOrderStatus($order, $status);
        }

        return $order->fresh() ?? $order;
    }

    private function applyOrderStatus(Order $order, OrderStatus $status): void
    {
        $path = match ($status) {
            OrderStatus::Paid => [OrderStatus::Paid],
            OrderStatus::Preparing => [OrderStatus::Paid, OrderStatus::Preparing],
            OrderStatus::Delivered => [OrderStatus::Paid, OrderStatus::Preparing, OrderStatus::Delivered],
            OrderStatus::Cancelled => [OrderStatus::Cancelled],
            OrderStatus::PendingPayment => [],
        };

        foreach ($path as $step) {
            $order->transitionTo($step);
        }

        $subStatus = SubOrderStatus::from($status->value);

        foreach ($order->subOrders as $subOrder) {
            $subPath = match ($subStatus) {
                SubOrderStatus::Paid => [SubOrderStatus::Paid],
                SubOrderStatus::Preparing => [SubOrderStatus::Paid, SubOrderStatus::Preparing],
                SubOrderStatus::Delivered => [SubOrderStatus::Paid, SubOrderStatus::Preparing, SubOrderStatus::Delivered],
                SubOrderStatus::Cancelled => [SubOrderStatus::Cancelled],
                SubOrderStatus::PendingPayment => [],
            };

            foreach ($subPath as $step) {
                $subOrder->transitionTo($step);
            }
        }
    }

    private function createPayment(
        User $user,
        Money $amount,
        PaymentPurpose $purpose,
        Model $payable,
        bool $succeeded,
    ): Payment {
        $payment = Payment::create([
            'user_id' => $user->id,
            'amount' => $amount,
            'currency' => Money::CURRENCY,
            'purpose' => $purpose,
            'payable_type' => $payable->getMorphClass(),
            'payable_id' => $payable->getKey(),
            'provider' => 'fake',
            'provider_reference' => 'PAY-'.Str::upper(Str::random(16)),
            'method' => PaymentMethod::MtnMomo,
            'status' => PaymentStatus::Initiated,
            'idempotency_key' => (string) Str::uuid(),
        ]);

        if ($succeeded) {
            $payment->markAsSucceeded();
        } else {
            $payment->markAsPending();
        }

        return $payment;
    }
}
