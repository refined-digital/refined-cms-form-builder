<?php

namespace RefinedDigital\FormBuilder\Module\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use RefinedDigital\CMS\Modules\Core\Models\CoreModel;
use RefinedDigital\FormBuilder\Module\Traits\Fields;
use Spatie\EloquentSortable\Sortable;

class Form extends CoreModel implements Sortable
{
    use Fields, SoftDeletes;

    protected $fillable = [
        'active',
        'position',
        'form_action',
        'send_as_plain_text',
        'name',
        'submit_text',
        'submit_action',
        'redirect_url',
        'subject',
        'email_to',
        'reply_to',
        'cc',
        'bcc',
        'callback',
        'message',
        'confirmation',
        'model',
        'recaptcha',
        'redirect_page',
        'receipt',
        'receipt_message',
        'receipt_subject',
        'use_pages',
        'autosave',
    ];

    protected $casts = [
        'id' => 'integer',
        'active' => 'integer',
        'position' => 'integer',
        'recaptcha' => 'integer',
        'send_as_plain_text' => 'integer',
        'receipt' => 'integer',
        'use_pages' => 'integer',
        'autosave' => 'integer',
    ];

    public function notifications()
    {
        return $this->hasMany(FormEmailNotification::class, 'form_id')->orderBy('position');
    }

    public function pages()
    {
        return $this->hasMany(FormPage::class, 'form_id')->orderBy('position');
    }

    public function integrations()
    {
        return $this->hasMany(FormIntegration::class, 'form_id');
    }

    /**
     * Legacy declarative edit-form tabs. The visual editor (rd-fb-editor) now
     * provides all editing UI and its own top tabs, so this is intentionally
     * empty — keeping it stops core's body-header rendering stale tab nav.
     *
     * @var array
     */
    public $formFields = [];
}
