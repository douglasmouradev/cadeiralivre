<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class SubscriptionServiceTest extends TestCase
{
    public function testCanOperateWithActiveTrial(): void
    {
        $svc = new \App\Services\SubscriptionService();
        $tenant = [
            'status' => 'trial',
            'trial_ends_at' => (new DateTimeImmutable('+7 days'))->format('Y-m-d H:i:s'),
            'subscription_status' => 'trialing',
        ];
        $this->assertTrue($svc->canOperate($tenant));
    }

    public function testCannotOperateWhenSuspended(): void
    {
        $svc = new \App\Services\SubscriptionService();
        $tenant = [
            'status' => 'suspended',
            'trial_ends_at' => null,
            'subscription_status' => 'active',
        ];
        $this->assertFalse($svc->canOperate($tenant));
    }
}
