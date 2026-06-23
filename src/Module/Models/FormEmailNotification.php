<?php

namespace RefinedDigital\FormBuilder\Module\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use RefinedDigital\CMS\Modules\Core\Models\CoreModel;
use Spatie\EloquentSortable\Sortable;

class FormEmailNotification extends CoreModel implements Sortable
{
    use SoftDeletes;

    protected $fillable = [
        'form_id', 'position', 'active', 'name',
        'to', 'cc', 'bcc', 'reply_to', 'subject', 'content',
    ];

    protected $casts = [
        'id' => 'integer',
        'form_id' => 'integer',
        'position' => 'integer',
        'active' => 'integer',
    ];

    public function form(): BelongsTo
    {
        return $this->belongsTo(Form::class, 'form_id');
    }

    public function buildSortQuery(): Builder
    {
        return static::query()->where('form_id', $this->form_id);
    }
}
