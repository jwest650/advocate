<?php

namespace Tests\Feature;

use Tests\TestCase;

class PricingPageRouteTest extends TestCase
{
    public function test_pricing_page_route_is_available(): void
    {
        $response = $this->get(route('pricing'));

        $response->assertOk();
    }
}
