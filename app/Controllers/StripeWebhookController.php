<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Response;
use App\Services\AppLogger;
use App\Services\StripeBillingService;
use Stripe\Webhook;

final class StripeWebhookController extends Controller
{
    public function handle(): Response
    {
        $secret = $_ENV['STRIPE_WEBHOOK_SECRET'] ?? '';
        if ($secret === '') {
            return Response::json(['error' => 'webhook_not_configured'], 503);
        }
        $payload = $this->request->getRawPayload();
        $sigHeader = $this->request->header('Stripe-Signature') ?? '';
        try {
            $event = Webhook::constructEvent($payload, $sigHeader, $secret);
        } catch (\Throwable $e) {
            AppLogger::info($this->app->root(), 'stripe_webhook_verify_failed', ['message' => $e->getMessage()]);

            return Response::json(['error' => 'invalid_payload'], 400);
        }
        try {
            (new StripeBillingService())->handleEvent($event);
        } catch (\Throwable $e) {
            AppLogger::exception($this->app->root(), $e);

            return Response::json(['error' => 'handler_failed'], 500);
        }

        return Response::json(['received' => true]);
    }
}
