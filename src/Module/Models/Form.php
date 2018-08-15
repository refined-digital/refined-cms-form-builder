<?php

namespace RefinedDigital\FormBuilder\Module\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use RefinedDigital\CMS\Modules\Core\Models\CoreModel;
use RefinedDigital\FormBuilder\Module\Traits\Fields;
use Spatie\EloquentSortable\Sortable;

class Form extends CoreModel implements Sortable
{
    use SoftDeletes, Fields;

    protected $fillable = [
        'active','position', 'form_action',
        'name', 'subject', 'email_to', 'reply_to', 'cc', 'bcc', 'payment',
        'callback', 'message', 'confirmation', 'model', 'recaptcha',
        'redirect_page', 'receipt','receipt_message','receipt_subject',
    ];

    /**
     * The fields to be displayed for creating / editing
     *
     * @var array
     */
    public $formFields = [
        [
            'name' => 'Content',
            'blocks' => [
                [
                    'name' => 'Config Details',
                    'fields' => [
                        [
                            [ 'count' => 4 ],
                            [ 'label' => 'Form Action', 'name' => 'form_action', 'required' => true, 'type' => 'select', 'options' => [1 => 'Email', 2 => 'Email in Callback', 3 => 'Model'], 'v-model' => 'form.action' ],
                            [ 'label' => 'ReCaptcha', 'name' => 'recaptcha', 'required' => true, 'type' => 'select', 'options' => [0 => 'No', 1 => 'v2', 2 => 'Invisible'] ],
                            [ 'label' => 'Send Receipt Email', 'name' => 'receipt', 'required' => true, 'type' => 'select', 'options' => [0 => 'No', 1 => 'Yes'], 'v-model' => 'form.receipt', 'row' => ['attrs' => ['v-if' => "form.action != '3'"]] ],
                            [ 'label' => 'Form Callback', 'name' => 'callback', 'row' => ['attrs' => ['v-if' => "form.action == '2'"]] ],
                            [ 'label' => 'Model to save to', 'name' => 'model', 'row' => ['attrs' => ['v-if' => "form.action == '3'"]] ],
                        ],
                        [
                            [ 'label' => 'Name', 'name' => 'name', 'required' => true],
                            [ 'label' => 'Subject', 'name' => 'subject', 'required' => true],
                        ],
                        [
                            [ 'label' => 'Email To', 'name' => 'email_to', 'required' => true, 'row' => ['attrs' => ['v-if' => "form.action != '3'"]] ],
                            [ 'label' => 'Reply To', 'name' => 'reply_to', 'required' => false, 'type' => 'replyTo', 'note' => 'Use <code>Enter Email Address</code> to enter an email address.<br/>Or, select an email field from your form. <br/><small>(Note: Come back once you have added all your form fields)</small>', 'row' => ['attrs' => ['v-if' => "form.action != '3'"]] ],
                            [ 'label' => 'CC', 'name' => 'cc', 'required' => false, 'row' => ['attrs' => ['v-if' => "form.action != '3'"]] ],
                            [ 'label' => 'BCC', 'name' => 'bcc', 'required' => false, 'row' => ['attrs' => ['v-if' => "form.action != '3'"]] ],
                        ],
                        [
                            [ 'count' => 4 ],
                            [ 'label' => 'Redirect to this page after form submission', 'name' => 'redirect_page', ],
                        ],
                    ]
                ],
                [
                    'name' => 'Messages',
                    'fields' => [
                        [
                            [ 'label' => 'Email Message', 'name' => 'message', 'required' => true, 'type' => 'richtext', 'pre_note' => 'Add <code>[[fields]]</code> to show the form fields', 'row' => ['attrs' => ['v-if' => "form.action != '3'"]] ],
                        ],
                        [
                            [ 'label' => 'On Screen Message', 'name' => 'confirmation', 'required' => true, 'type' => 'richtext'],
                        ],
                    ]
                ],
                [
                    'name' => 'Receipt',
                    'attrs' => ['v-if' => 'form.receipt'],
                    'fields' => [
                        [
                            [ 'label' => 'Subject', 'name' => 'receipt_subject', 'required' => true],
                        ],
                        [
                            [ 'label' => 'Email Message', 'name' => 'receipt_message', 'required' => true, 'type' => 'richtext', 'pre_note' => 'Add <code>[[fields]]</code> to show the form fields'],
                        ],
                    ]
                ]
            ]
        ]
    ];
}
