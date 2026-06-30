<?php

declare(strict_types=1);

use App\Helpers\EmailTemplate;
use PHPUnit\Framework\TestCase;

final class EmailTemplateTest extends TestCase
{
    public function testLayoutIncludesBrandAndBody(): void
    {
        $html = EmailTemplate::layout(
            EmailTemplate::paragraph('Teste de corpo'),
            'Barbearia Demo',
            '#7c5e3c',
        );

        $this->assertStringContainsString('Barbearia Demo', $html);
        $this->assertStringContainsString('Teste de corpo', $html);
        $this->assertStringContainsString('#7c5e3c', $html);
        $this->assertStringContainsString('<!DOCTYPE html>', $html);
    }

    public function testButtonEscapesHref(): void
    {
        $html = EmailTemplate::button('https://example.com/?a=1&b=2', 'Clique');

        $this->assertStringContainsString('https://example.com/?a=1&amp;b=2', $html);
        $this->assertStringContainsString('Clique', $html);
    }
}
