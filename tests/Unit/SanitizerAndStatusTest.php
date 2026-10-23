<?php

namespace Tests\Unit;

use App\Support\HtmlSanitizer;
use Tests\TestCase;

class SanitizerAndStatusTest extends TestCase
{
    public function test_sanitizer_strips_scripts_handlers_and_unsafe_urls(): void
    {
        $dirty = '<p onclick="x()">Hi <script>alert(1)</script><a href="javascript:alert(1)">bad</a> <a href="https://ok.example">ok</a><img src="javascript:1" onerror="x()"></p>';
        $clean = HtmlSanitizer::clean($dirty);
        $this->assertStringNotContainsString('script', $clean);
        $this->assertStringNotContainsString('onclick', $clean);
        $this->assertStringNotContainsString('javascript:', $clean);
        $this->assertStringContainsString('https://ok.example', $clean);
    }

    public function test_customer_facing_version_drops_internal_blocks(): void
    {
        $html = '<p>Public</p><div data-visibility="internal"><p>Secret</p></div><p>Also public</p>';
        $out = HtmlSanitizer::customerFacing($html);
        $this->assertStringContainsString('Public', $out);
        $this->assertStringNotContainsString('Secret', $out);
    }

    public function test_every_status_dimension_has_the_spec_values(): void
    {
        $this->assertSame(['draft', 'pending_review', 'accepted', 'rejected'], array_keys(config('statuses.lead_review.values')));
        $this->assertCount(12, config('statuses.sales.values'));
        $this->assertCount(9, config('statuses.audit.values'));
        $this->assertCount(10, config('statuses.agreement.values'));
        $this->assertCount(7, config('statuses.billing.values'));
        $this->assertCount(8, config('statuses.commission.values'));
        $this->assertTrue(config('statuses.sales.values.closed_lost')[2]['reason']);
    }
}
