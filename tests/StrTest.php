<?php

declare(strict_types=1);

namespace Tests;

use App\Helpers\Str;
use PHPUnit\Framework\TestCase;

final class StrTest extends TestCase
{
    public function testSlugNormalizesText(): void
    {
        $this->assertSame('hello-world', Str::slug('Hello World'));
        $this->assertSame('abc-123', Str::slug('abc 123'));
    }
}
