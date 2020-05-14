<?php

namespace RefinedDigital\FormBuilder\Module\Models;

use RefinedDigital\CMS\Modules\Core\Models\CoreModel;

class FormPaymentTransaction extends CoreModel
{
    protected $fillable = [
        'form_id',
        'type_id',
        'type_details',
        'transaction_id',
        'request',
        'response',
    ];

    protected $casts = [
        'request' => 'object',
        'response' => 'object',
    ];
}
