<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class PlatformHelpersTest extends TestCase
{
    protected function setUp(): void
    {
        $_ENV['DEMO_BOOKING_SLUG'] = 'minha-loja-demo';
        $_ENV['DEMO_BOOKING_LABEL'] = 'Loja Demo';
        $_ENV['MAIL_FROM_ADDRESS'] = 'noreply@example.com';
    }

    public function testDemoBookingHelpers(): void
    {
        $this->assertSame('minha-loja-demo', demo_booking_slug());
        $this->assertSame('/agendar/minha-loja-demo', demo_booking_url());
        $this->assertSame('Loja Demo', demo_booking_label());
    }

    public function testMailConfigured(): void
    {
        $this->assertTrue(mail_configured());
        $_ENV['MAIL_FROM_ADDRESS'] = '';
        $this->assertFalse(mail_configured());
    }
}
