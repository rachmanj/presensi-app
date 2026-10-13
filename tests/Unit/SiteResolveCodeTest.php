<?php

namespace Tests\Unit;

use App\Models\Site;
use Tests\TestCase;

class SiteResolveCodeTest extends TestCase
{
    public function test_resolves_hero_head_office_project_code_to_ho(): void
    {
        $this->assertSame('HO', Site::resolveCode('000H'));
    }

    public function test_returns_unaliased_codes_unchanged(): void
    {
        $this->assertSame('017C', Site::resolveCode('017C'));
        $this->assertSame('APS', Site::resolveCode('APS'));
        $this->assertSame('CUSTOM99', Site::resolveCode('CUSTOM99'));
    }

    public function test_returns_null_for_empty_values(): void
    {
        $this->assertNull(Site::resolveCode(null));
        $this->assertSame('', Site::resolveCode(''));
    }
}
