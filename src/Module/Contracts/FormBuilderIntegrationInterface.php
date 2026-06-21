<?php

namespace RefinedDigital\FormBuilder\Module\Contracts;

/**
 * Implemented by an integration package's processor (registered via the core
 * FormBuilderIntegrationAggregate). Replaces the legacy FormBuilderCallbackInterface.
 */
interface FormBuilderIntegrationInterface
{
    /**
     * Process a submission. MUST NOT send notification emails (the form owns that).
     *
     * Return null / a success result to continue the submission. Return a failure
     * result (object/array with success=false + message) — or throw — to ABORT the
     * whole submission with a 422 (no notifications, no later integrations, no
     * redirect). This generic halt-on-failure is what Payments uses for a declined
     * charge.
     *
     * @param  mixed  $request  the submit request
     * @param  object $form     the Form
     * @param  array  $settings per-form integration config (enabled, send_email, config)
     * @return mixed
     */
    public function process($request, $form, $settings);
}
