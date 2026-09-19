<?php

declare(strict_types=1);

namespace App\Http\Controllers\Payments;

use App\Http\Controllers\Controller;
use App\Payments\Contracts\PaymentGateway;
use App\Payments\Exceptions\InvalidCallbackSignature;
use App\Payments\Exceptions\PaymentOutcomeNotHandled;
use App\Payments\Gateways\FakeMobileMoneyGateway;
use App\Services\Payments\PaymentService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * The only door through which a payment may be confirmed.
 *
 * Exempt from CSRF — this is a server-to-server call, there is no session and
 * no token to carry — and protected instead by the HMAC signature the gateway
 * puts on every callback, checked before a single byte of the payload is read.
 *
 * Business rule RG06 lives here: no browser redirect reaches this controller,
 * and nothing else in the application may mark a payment successful.
 */
final class WebhookController extends Controller
{
    public function __invoke(
        Request $request,
        PaymentGateway $gateway,
        PaymentService $payments,
    ): JsonResponse {
        // Logged before verification precisely because a rejected callback is
        // the one worth investigating.
        Log::info('Callback de paiement reçu.', [
            'ip' => $request->ip(),
            'payload' => $request->getContent(),
        ]);

        try {
            $payload = $gateway->verifyCallback($request);
        } catch (InvalidCallbackSignature $exception) {
            Log::warning('Callback de paiement rejeté.', ['raison' => $exception->getMessage()]);

            return response()->json(['message' => 'Signature invalide.'], 403);
        }

        try {
            $applied = $payments->applyCallback(
                $payload,
                (string) $request->header(FakeMobileMoneyGateway::SIGNATURE_HEADER),
            );
        } catch (ModelNotFoundException) {
            // Answering 404 tells a prober which references exist. A gateway
            // has nothing useful to do with the distinction anyway.
            Log::warning('Callback de paiement sans paiement correspondant.', [
                'reference' => $payload->providerReference,
            ]);

            return response()->json(['message' => 'Callback reçu.']);
        } catch (PaymentOutcomeNotHandled $exception) {
            Log::error('Effet métier manquant pour ce paiement.', [
                'reference' => $payload->providerReference,
                'raison' => $exception->getMessage(),
            ]);

            throw $exception;
        }

        return response()->json([
            'message' => 'Callback reçu.',
            'applied' => $applied,
        ]);
    }
}
