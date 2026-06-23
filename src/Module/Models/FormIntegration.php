<?php

namespace RefinedDigital\FormBuilder\Module\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FormIntegration extends Model
{
    protected $fillable = [
        'form_id', 'integration_key', 'enabled', 'send_email', 'config',
    ];

    protected $casts = [
        'id' => 'integer',
        'form_id' => 'integer',
        'enabled' => 'boolean',
        'send_email' => 'boolean',
        'config' => 'array',
    ];

    public function form(): BelongsTo
    {
        return $this->belongsTo(Form::class, 'form_id');
    }
}
