<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Payments\Gateways\FakeMobileMoneyGateway;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Delivers a signed callback from the simulated gateway to the webhook.
 *
 * It goes through the queue on purpose: a real operator answers out of band,
 * some seconds later, on its own connection. Running it inline would hide
 * every bug that only shows up when the answer arrives after the browser has
 * already moved on.
 *
 * The delivery is a real HTTP request handled by the application kernel, so
 * routing, middleware and signature verification all run exactly as they would
 * for an operator's call — without needing a network, a running web server, or
 * an internet connection.
 */
final class DeliverSimulatedCallback implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly string $providerReference,
        public readonly PaymentStatus $outcome,
        public readonly string $eventId,
    ) {}

    public function handle(): void
    {
        $payment = Payment::query()
            ->where('provider_reference', $this->providerReference)
            ->first();

        if (! $payment instanceof Payment) {
            Log::warning('Callback simulé abandonné : paiement introuvable.', [
                'reference' => $this->providerReference,
            ]);

            return;
        }

        $body = json_encode([
            'event_id' => $this->eventId,
            'reference' => $payment->provider_reference,
            'status' => $this->outcome->value,
            'amount' => $payment->amount->amount,
            'currency' => $payment->currency,
            'method' => $payment->method->value,
            'occurred_at' => now()->toIso8601String(),
        ], JSON_THROW_ON_ERROR);

        $timestamp = (string) now()->getTimestamp();

        $request = Request::create(
            uri: route('webhooks.payment'),
            method: 'POST',
            server: [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_'.str_replace('-', '_', strtoupper(FakeMobileMoneyGateway::SIGNATURE_HEADER)) => FakeMobileMoneyGateway::sign($timestamp, $body),
                'HTTP_'.str_replace('-', '_', strtoupper(FakeMobileMoneyGateway::TIMESTAMP_HEADER)) => $timestamp,
            ],
            content: $body,
        );

        $response = app()->handle($request);

        Log::info('Callback simulé délivré.', [
            'reference' => $payment->provider_reference,
            'event_id' => $this->eventId,
            'statut' => $this->outcome->value,
            'réponse' => $response->getStatusCode(),
        ]);
    }
}
