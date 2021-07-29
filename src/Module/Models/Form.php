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
        'active','position', 'form_action', 'send_as_plain_text',
        'name', 'subject', 'email_to', 'reply_to', 'cc', 'bcc', 'payment',
        'callback', 'message', 'confirmation', 'model', 'recaptcha',
        'redirect_page', 'receipt','receipt_message','receipt_subject',
    ];

    protected $casts = [
      'id' => 'integer',
      'active' => 'integer',
      'position' => 'integer',
      'recaptcha' => 'integer',
      'send_as_plain_text' => 'integer',
      'receipt' => 'integer',
    ];

    /**
     * The fields to be displayed for creating / editing
     *
     * @var array
     */
    public $formFields = [
      [
        'name' => 'Config Details',
        'sections' => [
          'left' => [
            'blocks' => [
              [
                'name' => 'Settings',
                'fields' => [
                  [
                    [ 'label' => 'Name', 'name' => 'name', 'required' => true],
                    [ 'label' => 'Subject', 'name' => 'subject', 'required' => true],
                  ],
                  [
                    [ 'label' => 'Email To', 'name' => 'email_to', 'required' => true, 'type' => 'emails', 'row' => ['attrs' => ['v-if' => "form.action != '3'"]] ],
                    [ 'label' => 'Reply To', 'name' => 'reply_to', 'required' => false, 'type' => 'replyTo', 'note' => 'Use <code>Enter Email Address</code> to enter an email address.<br/>Or, select an email field from your form. <br/><small>(Note: Come back once you have added all your form fields)</small>', 'row' => ['attrs' => ['v-if' => "form.action != '3'"]] ],
                  ],
                  [
                    [ 'label' => 'CC', 'name' => 'cc', 'required' => false, 'type' => 'emails', 'row' => ['attrs' => ['v-if' => "form.action != '3'"]] ],
                    [ 'label' => 'BCC', 'name' => 'bcc', 'required' => false, 'type' => 'emails', 'row' => ['attrs' => ['v-if' => "form.action != '3'"]] ],
                  ],
                ]
              ]
            ]
          ],
          'right' => [
            'blocks' => [
              [
                'name' => 'Config Details',
                'fields' => [
                  [
                    [ 'count' => 1 ],
                    [ 'label' => 'Form Action', 'name' => 'form_action', 'required' => true, 'type' => 'select', 'options' => [1 => 'Email', 2 => 'Email in Callback', 3 => 'Model'], 'v-model' => 'form.action' ],
                    [ 'label' => 'Form Callback', 'name' => 'callback', 'row' => ['attrs' => ['v-if' => "form.action == '2'"]] ],
                    [ 'label' => 'Model to save to', 'name' => 'model', 'row' => ['attrs' => ['v-if' => "form.action == '3'"]] ],
                    [ 'label' => 'ReCaptcha', 'name' => 'recaptcha', 'required' => true, 'type' => 'select', 'options' => [0 => 'No', 1 => 'v2', 2 => 'Invisible'] ],
                    [ 'label' => 'Send Receipt Email', 'name' => 'receipt', 'required' => true, 'type' => 'select', 'options' => [0 => 'No', 1 => 'Yes'], 'v-model' => 'form.receipt', 'row' => ['attrs' => ['v-if' => "form.action != '3'"]] ],
                    [ 'label' => 'Send as Plain Text', 'name' => 'send_as_plain_text', 'required' => true, 'type' => 'select', 'options' => [0 => 'No', 1 => 'Yes'], 'row' => ['attrs' => ['v-if' => "form.action != '3'"]] ],
                    [ 'label' => 'Redirect to this page after form submission', 'name' => 'redirect_page', 'type' => 'link' ],
                  ],
                ]
              ],
            ]
          ]
        ]
      ],
      [
        'name' => 'Messages',
        'blocks' => [
          [
            'name' => 'Email Message',
            'fields' => [
              [
                [ 'label' => 'Email Message', 'name' => 'message', 'required' => true, 'type' => 'richtext', 'pre_note' => 'Add <code>[[fields]]</code> to show the form fields', 'row' => ['attrs' => ['v-if' => "form.action != '3'"]] ],
              ],
            ]
          ],
          [
            'name' => 'On Screen Message',
            'fields' => [
              [
                [ 'label' => 'On Screen Message', 'name' => 'confirmation', 'required' => true, 'type' => 'richtext'],
              ],
            ]
          ],
        ]
      ],
      [
        'name' => 'Receipt',
        'attrs' => ['v-if' => 'form.receipt == 1'],
        'fields' => [
          [
            [ 'label' => 'Subject', 'name' => 'receipt_subject', 'required' => true],
          ],
          [
            [ 'label' => 'Email Message', 'name' => 'receipt_message', 'required' => true, 'type' => 'richtext', 'pre_note' => 'Add <code>[[fields]]</code> to show the form fields'],
          ],
        ]
      ]
    ];
}
