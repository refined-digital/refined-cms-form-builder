<?php

namespace RefinedDigital\FormBuilder\Module\Support;

use Illuminate\Support\Facades\Http;

/**
 * Optional ping to the Care Plans app so each form submission is counted as an
 * enquiry. Off unless enabled with an endpoint and token. Runs after the
 * response (no queue worker needed) and can never affect the submission.
 */
class CarePlanEnquiryPing
{
    /** Posts the submission's idempotency key to the Care Plans endpoint after the response. */
    public static function dispatch(string $idempotencyKey): void
    {
        $config = config('form-builder.care_plan_enquiries', []);
        $endpoint = $config['endpoint'] ?? null;
        $token = $config['token'] ?? null;

        if (empty($config['enabled']) || empty($endpoint) || empty($token)) {
            return;
        }

        dispatch(function () use ($endpoint, $token, $idempotencyKey) {
            try {
                Http::withToken($token)
                    ->timeout(5)
                    ->acceptJson()
                    ->post($endpoint, [
                        'idempotency_key' => $idempotencyKey,
                        'occurred_at' => now()->toIso8601String(),
                    ]);
            } catch (\Throwable $e) {
                report($e);
            }
        })->afterResponse();
    }
}
