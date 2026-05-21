<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Stripe\EphemeralKey;
use Stripe\StripeClient;

class StripeService
{
    protected ?StripeClient $stripe = null;

    protected string $secretKey = '';

    protected string $webhookSecret = '';

    public function __construct()
    {
        $this->secretKey     = config('services.stripe.secret', env('STRIPE_SECRET'));
        $this->webhookSecret = config('services.stripe.webhook_secret', env('STRIPE_WEBHOOK_SECRET'));

        if ($this->secretKey !== '') {
            \Stripe\Stripe::setApiKey($this->secretKey);
            $this->stripe = new StripeClient($this->secretKey);
        }
    }

    protected function assertStripeConfigured(): void
    {
        if ($this->secretKey === '' || $this->stripe === null) {
            throw new RuntimeException('Stripe is not configured. Set STRIPE_SECRET in your environment.');
        }
    }

    protected function client(): StripeClient
    {
        $this->assertStripeConfigured();

        return $this->stripe;
    }

   
    public function createCustomer(User $user): string
    {
        $this->assertStripeConfigured();

        $customer = \Stripe\Customer::create([
            'name'  => $user->name,
            'phone' => $user->phone,
        ]);

        $user->update(['stripe_customer_id' => $customer->id]);

        return $customer->id;
    }

    
    public function ensureCustomer(User $user): string
    {
        if (! $user->stripe_customer_id) {
            return $this->createCustomer($user);
        }

        return $user->stripe_customer_id;
    }

    
   /* public function createPaymentIntent(User $user, float $amount, string $paymentMethod, int $orderId,  int $points = 0, string $type = null ): array
    {
        $customerId = $this->ensureCustomer($user);

        $ephemeralKey = EphemeralKey::create(
            ['customer' => $customerId],
            ['stripe_version' => '2026-01-28.clover']
        );

        $paymentIntent = \Stripe\PaymentIntent::create([
            'amount'               => (int) round($amount * 100), // convert to cents
            'currency'             => 'usd',
            'payment_method_types' => ['card'],
            'customer'             => $customerId,
            'payment_method'       => $paymentMethod,
            'off_session'          => false,
            'confirm'              => false,
            'setup_future_usage'   => 'off_session',
            'metadata'             => [
                'order_id' => $orderId,
                'user_id'  => $user->id,
                'type'     => $type,
                'points'   => $points,
            ],
        ]);

        return [
            'payment_intent' => $paymentIntent,
            'ephemeral_key'  => $ephemeralKey,
        ];
    }*/


    public function createPaymentIntent(User $user, float $amount, ?string $paymentMethod = null, int $orderId, int $points = 0, string $type = null): array
    {
        $this->assertStripeConfigured();

        $customerId = $this->ensureCustomer($user);

        $ephemeralKey = EphemeralKey::create(
            ['customer' => $customerId],
            ['stripe_version' => '2026-01-28.clover'] 
        );

        $params = [
            'amount'               => (int) round($amount * 100),
            'currency'             => 'usd',
            'customer'             => $customerId,
            'setup_future_usage'   => 'off_session',
            'metadata'             => [
                'order_id' => $orderId,
                'user_id'  => $user->id,
                'type'     => $type,
                'points'   => $points,
            ],

            'automatic_payment_methods' => [
                'enabled' => true,
            ],
        ];
        
        if ($paymentMethod) {
            $params['payment_method'] = $paymentMethod;
        }

        $paymentIntent = \Stripe\PaymentIntent::create($params);

        return [
            'payment_intent' => $paymentIntent,
            'ephemeral_key'  => $ephemeralKey,
        ];
    }



    public function charge(User $user, float $salary, int $points, string $paymentMethod): array
    {
        $this->assertStripeConfigured();

        $customerId = $this->ensureCustomer($user);

        $ephemeralKey = EphemeralKey::create(
            ['customer' => $customerId],
            ['stripe_version' => '2026-01-28.clover']
        );

        $paymentIntent = \Stripe\PaymentIntent::create([
            'amount'               => (int) round($salary * 100), // convert to cents
            'currency'             => 'usd',
            'payment_method_types' => ['card'],
            'customer'             => $customerId,
            'payment_method'       => $paymentMethod,
            'off_session'          => false,
            'confirm'              => false,
            'metadata'             => [
                'user_id'  => $user->id,
                'type'     => 'points',
                'points'   => $points,
            ],
        ]);

        return [
            'payment_intent' => $paymentIntent,
            'ephemeral_key'  => $ephemeralKey,
            'stripe_customer_id' => $user?->stripe_customer_id ?? null,
        ];
    }

    //  Webhook
    public function constructWebhookEvent(string $payload, string $sigHeader): \Stripe\Event
    {
        if ($this->webhookSecret === '') {
            throw new RuntimeException('Stripe webhook is not configured. Set STRIPE_WEBHOOK_SECRET in your environment.');
        }

        return \Stripe\Webhook::constructEvent($payload, $sigHeader, $this->webhookSecret);
    }

   
    //callack
    public function handlePaymentSucceeded(\Stripe\Event $event): void
    {
        $paymentIntent = $event->data->object;
        $userId  = $paymentIntent->metadata->user_id ?? null;
        $orderId = $paymentIntent->metadata->order_id ?? null;

        $user  = $userId ? User::find($userId) : null;
        $order = $orderId ? Order::find($orderId) : null;

        if (!$order || $order->payment_status === 'paid') {
            return;
        }

        if ($user && $paymentIntent->payment_method) {
            $user->update(['stripe_payment_method' => $paymentIntent->payment_method]);

            $this->client()->paymentMethods->attach(
                $paymentIntent->payment_method,
                ['customer' => $user->stripe_customer_id]
            );
        }

        DB::transaction(function () use ($order, $user, $paymentIntent) {
            $status = ($paymentIntent->metadata->type === 'scheduled') ? 'scheduled' : 'paid';
            
            $order->update([
                'status' => $status,
                'payment_status' => 'paid'
            ]);

            foreach ($order->items as $item) {
                $product = Product::lockForUpdate()->find($item->product_id);
                if ($product) {
                    $product->decrement('remaining_quantity', $item->quantity);
                    $product->increment('total_sales', $item->quantity);
                }
            }

            $rank = $user ? $user->rank() : null;
            $totalPointsToAdd = 0;

            foreach ($order->items as $item) {
                $basePoints = $item->product->points * $item->quantity;
                $totalPointsToAdd += $rank ? ($basePoints * $rank->points_increment) : $basePoints;
            }

            $extraPoints = (int) ($paymentIntent->metadata->points ?? 0);
            $totalPointsToAdd += $extraPoints;

            if ($totalPointsToAdd > 0) {
                $user->increment('total_points', $totalPointsToAdd);
                $order->update(['points_increase_user' => $totalPointsToAdd]);
            }

            if ($user && $user->cart) {
                $user->cart->items()->each(function ($item) {
                    $item->optionValues()->detach();
                    $item->delete();
                });
                $user->cart->delete();
            }
        });
    }

    public function handlePaymentFailed(\Stripe\Event $event): void
    {
        $paymentIntent = $event->data->object;

        $userId  = $paymentIntent->metadata->user_id  ?? null;
        $orderId = $paymentIntent->metadata->order_id ?? null;

        $user  = $userId  ? User::find($userId)  : null;
        $order = $orderId ? Order::find($orderId) : null;

        if ($order) {
            $order->update(['status' => 'canceled']);
        }

        if ($user && isset($paymentIntent->last_payment_error->payment_method->id)) {
            $user->update([
                'stripe_payment_method' => $paymentIntent->last_payment_error->payment_method->id,
            ]);
        }
    }

    //  CRUD Payment Methods
    public function attachPaymentMethod(User $user, string $paymentMethodId): \Stripe\PaymentMethod
    {
        $this->assertStripeConfigured();
        $this->ensureCustomer($user);

        return $this->client()->paymentMethods->attach(
            $paymentMethodId,
            ['customer' => $user->stripe_customer_id]
        );
    }

    public function retrievePaymentMethod(User $user, string $paymentMethodId): \Stripe\PaymentMethod
    {
        $this->assertStripeConfigured();

        return $this->client()->customers->retrievePaymentMethod(
            $user->stripe_customer_id,
            $paymentMethodId,
            []
        );
    }

    public function listPaymentMethods(User $user): \Stripe\Collection
    {
        $this->assertStripeConfigured();
        $customerId = $this->ensureCustomer($user);

        return $this->client()->customers->allPaymentMethods(
            $customerId,
            ['limit' => 10]
        );
    }

    public function detachPaymentMethod(string $paymentMethodId): \Stripe\PaymentMethod
    {
        $this->assertStripeConfigured();

        return $this->client()->paymentMethods->detach($paymentMethodId, []);
    }
}
