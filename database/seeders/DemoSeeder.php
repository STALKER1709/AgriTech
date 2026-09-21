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
use App\Models\TrainingPurchase;
use App\Models\User;
use App\Support\Money;
use App\Support\PlaceholderImage;
use App\Support\Quantity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
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

        Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $activeFarmer->id,
            'content' => 'Bonjour, oui, la récolte de jeudi sera disponible dès vendredi matin.',
            'read_at' => null,
            'created_at' => now()->subHours(2),
            'updated_at' => now()->subHours(2),
        ]);
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
        if (! PlaceholderImage::isSupported()) {
            return;
        }

        $path = 'training-covers/'.$training->slug.'.png';
        $disk = Storage::disk((string) config('catalog.images.disk', 'local'));

        if (! $disk->exists($path)) {
            $disk->put($path, PlaceholderImage::cover($scene));
        }
    }

    /**
     * @return array<string, Training>
     */
    private function createTrainings(User $firstFarmer, User $secondFarmer): array
    {
        $definitions = [
            'compostage' => [$firstFarmer, 'Composter ses déchets agricoles', 7500, TrainingFormat::Video, false],
            'irrigation' => [$firstFarmer, 'Irrigation goutte à goutte à petit budget', 5000, TrainingFormat::Mixed, true],
            'cacao' => [$secondFarmer, 'Entretenir une cacaoyère productive', 12000, TrainingFormat::Video, true],
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
        }

        return $trainings;
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
