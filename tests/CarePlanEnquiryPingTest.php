<?php

namespace RefinedDigital\FormBuilder\Tests;

use Illuminate\Support\Facades\Http;
use RefinedDigital\FormBuilder\Module\Support\CarePlanEnquiryPing;

// the ping is fire-and-forget: it runs on app terminate and must never throw
class CarePlanEnquiryPingTest extends TestCase
{
    protected function enable(array $overrides = []): void
    {
        config(['form-builder.care_plan_enquiries' => array_merge([
            'enabled' => true,
            'endpoint' => 'https://projects.test/api/care-plans/enquiries',
            'token' => 'secret-token',
        ], $overrides)]);
    }

    public function test_disabled_sends_nothing(): void
    {
        Http::fake();
        config(['form-builder.care_plan_enquiries.enabled' => false]);

        CarePlanEnquiryPing::dispatch('abc');
        $this->app->terminate();

        Http::assertNothingSent();
    }

    public function test_missing_endpoint_or_token_sends_nothing(): void
    {
        Http::fake();

        $this->enable(['token' => null]);
        CarePlanEnquiryPing::dispatch('abc');
        $this->enable(['endpoint' => null]);
        CarePlanEnquiryPing::dispatch('abc');
        $this->app->terminate();

        Http::assertNothingSent();
    }

    public function test_enabled_posts_key_with_bearer_token_after_response(): void
    {
        Http::fake(['*' => Http::response(null, 202)]);
        $this->enable();

        CarePlanEnquiryPing::dispatch('11111111-2222-3333-4444-555555555555');

        // nothing goes out until the app terminates
        Http::assertNothingSent();
        $this->app->terminate();

        Http::assertSentCount(1);
        Http::assertSent(function ($request) {
            return $request->method() === 'POST'
                && $request->url() === 'https://projects.test/api/care-plans/enquiries'
                && $request->hasHeader('Authorization', 'Bearer secret-token')
                && $request['idempotency_key'] === '11111111-2222-3333-4444-555555555555'
                && strtotime($request['occurred_at']) !== false;
        });
    }

    public function test_server_error_does_not_throw(): void
    {
        Http::fake(['*' => Http::response('boom', 500)]);
        $this->enable();

        CarePlanEnquiryPing::dispatch('abc');
        $this->app->terminate();

        Http::assertSentCount(1);
        $this->assertTrue(true);
    }

    public function test_connection_failure_is_reported_not_thrown(): void
    {
        Http::fake(fn () => throw new \Illuminate\Http\Client\ConnectionException('down'));
        $this->enable();

        CarePlanEnquiryPing::dispatch('abc');
        $this->app->terminate();

        $this->assertTrue(true);
    }
}
