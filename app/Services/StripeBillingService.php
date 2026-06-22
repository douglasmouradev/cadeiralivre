<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\PlanDefinitionModel;
use App\Models\TenantModel;
use Stripe\Checkout\Session;
use Stripe\Event;
use Stripe\StripeObject;
use Stripe\Subscription;

final class StripeBillingService
{
    public function handleEvent(Event $event): void
    {
        match ($event->type) {
            'customer.subscription.updated', 'customer.subscription.deleted' => $this->onSubscriptionObject($event->data->object),
            'checkout.session.completed' => $this->onCheckoutSession($event->data->object),
            default => null,
        };
    }

    private function onSubscriptionObject(StripeObject $obj): void
    {
        if (!$obj instanceof Subscription) {
            return;
        }
        $this->syncSubscription($obj);
    }

    private function onCheckoutSession(StripeObject $obj): void
    {
        if (!$obj instanceof Session) {
            return;
        }
        $ref = $obj->subscription ?? null;
        if ($ref instanceof Subscription) {
            $this->syncSubscription($ref);

            return;
        }
        $subId = is_string($ref) ? $ref : null;
        if ($subId === null || $subId === '') {
            return;
        }
        $stripe = $this->client();
        $sub = $stripe->subscriptions->retrieve($subId, []);
        $this->syncSubscription($sub);
    }

    private function syncSubscription(Subscription $sub): void
    {
        $tenants = new TenantModel();
        $tenant = $tenants->findByBillingSubscriptionId($sub->id);
        if ($tenant === null) {
            $metaTid = $sub->metadata['tenant_id'] ?? null;
            if ($metaTid !== null && $metaTid !== '') {
                $tenant = $tenants->findById((int) $metaTid);
            }
        }
        if ($tenant === null) {
            AppLogger::info(dirname(__DIR__, 2), 'stripe_subscription_no_tenant', ['subscription' => $sub->id]);

            return;
        }
        $tid = (int) $tenant['id'];
        $statusMap = [
            'active' => 'active',
            'trialing' => 'trialing',
            'past_due' => 'past_due',
            'canceled' => 'canceled',
            'unpaid' => 'past_due',
            'incomplete_expired' => 'canceled',
        ];
        $raw = (string) $sub->status;
        $subStatus = $statusMap[$raw] ?? 'none';

        $priceId = null;
        $items = $sub->items->data[0] ?? null;
        if ($items !== null && isset($items->price) && is_object($items->price)) {
            $priceId = (string) ($items->price->id ?? '');
        }
        $planRow = ($priceId !== null && $priceId !== '') ? (new PlanDefinitionModel())->findByStripePriceId($priceId) : null;

        $fields = [
            'subscription_status' => $subStatus,
            'billing_provider' => 'stripe',
            'billing_customer_id' => (string) $sub->customer,
            'billing_subscription_id' => $sub->id,
        ];
        if ($planRow !== null) {
            $fields['plan'] = (string) $planRow['slug'];
            $fields['plan_definition_id'] = (int) $planRow['id'];
        }
        $tenants->updateBilling($tid, $fields);
        if (in_array($subStatus, ['active', 'trialing'], true)) {
            $tenants->setStatus($tid, 'active');
        }
    }

    /** Cria sessão Stripe Checkout e devolve a URL de redirecionamento. */
    public function createCheckoutUrl(int $tenantId, string $stripePriceId, string $successUrl, string $cancelUrl): string
    {
        $tenant = (new TenantModel())->findById($tenantId);
        if ($tenant === null) {
            throw new \RuntimeException('Loja não encontrada.');
        }
        if ($stripePriceId === '') {
            throw new \RuntimeException('Plano sem preço Stripe configurado.');
        }
        $stripe = $this->client();
        $customerId = (string) ($tenant['billing_customer_id'] ?? '');
        $params = [
            'mode' => 'subscription',
            'line_items' => [['price' => $stripePriceId, 'quantity' => 1]],
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
            'metadata' => ['tenant_id' => (string) $tenantId],
            'subscription_data' => ['metadata' => ['tenant_id' => (string) $tenantId]],
        ];
        if ($customerId !== '') {
            $params['customer'] = $customerId;
        } else {
            $params['customer_email'] = (string) ($tenant['email'] ?? '');
        }
        $session = $stripe->checkout->sessions->create($params);
        $url = (string) ($session->url ?? '');
        if ($url === '') {
            throw new \RuntimeException('Stripe não retornou URL de checkout.');
        }

        return $url;
    }

    private function client(): \Stripe\StripeClient
    {
        $key = $_ENV['STRIPE_SECRET_KEY'] ?? '';
        if ($key === '') {
            throw new \RuntimeException('STRIPE_SECRET_KEY não configurada.');
        }

        return new \Stripe\StripeClient($key);
    }
}
