<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class WhatsappLinkTest extends TestCase
{
    public function testBuildsBrazilianNumber(): void
    {
        $url = whatsapp_link('71997087082', 'Olá');
        $this->assertStringContainsString('wa.me/5571997087082', $url);
        $this->assertStringContainsString('text=Ol', $url);
    }

    public function testEmptyPhoneReturnsEmpty(): void
    {
        $this->assertSame('', whatsapp_link('', 'msg'));
    }
}
