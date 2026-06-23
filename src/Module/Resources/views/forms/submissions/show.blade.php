@extends('core::layouts.master')

@section('title', $heading.' — Submission')

@section('template')

<div class="app__content-header">
    <h2>
        <a href="{{ route('refined.form-builder.index') }}">{{ $heading }}</a> /
        <a href="{{ $backRoute }}">{{ $form->name }} — Submissions</a>:
        <span>{{ $submission->created_at->timezone(config('form-builder.timezone'))->format(config('form-builder.datetime_format', 'd/m/Y g:ia')) }}</span>
    </h2>
    <aside>
        <a href="{{ $backRoute }}" class="button button--grey">Back to Submissions</a>
        <a href="{{ route('refined.form-builder.edit', $form) }}" class="button button--blue">Edit Form</a>
    </aside>
</div>

<div class="app__content">
    <div class="fb-sub-detail">

        {{-- left: the submitted form values --}}
        <div class="block">
            <header><h3>Submission</h3></header>
            <div>
                @if (count($submission->fields))
                    <dl class="fb-sub-fields">
                        @foreach ($submission->fields as $field)
                            <div class="fb-sub-fields__row">
                                <dt>{{ $field->name }}</dt>
                                <dd>{!! $field->value !== '' && $field->value !== null ? $field->value : '<span class="fb-sub-muted">—</span>' !!}</dd>
                            </div>
                        @endforeach
                    </dl>
                @else
                    <p class="fb-sub-muted">No field data was stored for this submission.</p>
                @endif
            </div>
        </div>

        {{-- right: the delivery details, one block per notification --}}
        <aside class="fb-sub-side">
            <h3 class="fb-sub-side__heading">Notifications</h3>
            @foreach ($submission->notifications as $note)
                <div class="block">
                    <header><h3>{{ $note->name ?: 'Notification' }}</h3></header>
                    <div>
                        <dl class="fb-sub-meta">
                            <div class="fb-sub-meta__row"><dt>Sent</dt><dd>{{ $note->created_at->timezone(config('form-builder.timezone'))->format(config('form-builder.datetime_format', 'd/m/Y g:ia')) }}</dd></div>
                            @if ($note->subject)<div class="fb-sub-meta__row"><dt>Subject</dt><dd>{{ $note->subject }}</dd></div>@endif
                            <div class="fb-sub-meta__row"><dt>To</dt><dd>{{ $note->to ?: '—' }}</dd></div>
                            @if ($note->cc)<div class="fb-sub-meta__row"><dt>CC</dt><dd>{{ $note->cc }}</dd></div>@endif
                            @if ($note->bcc)<div class="fb-sub-meta__row"><dt>BCC</dt><dd>{{ $note->bcc }}</dd></div>@endif
                            @if ($note->reply_to)<div class="fb-sub-meta__row"><dt>Reply-To</dt><dd>{{ $note->reply_to }}</dd></div>@endif
                            @if ($note->from)<div class="fb-sub-meta__row"><dt>From</dt><dd>{{ $note->from }}</dd></div>@endif
                            <div class="fb-sub-meta__row"><dt>IP</dt><dd>{{ $note->ip ?: '—' }}</dd></div>
                        </dl>
                    </div>
                </div>
            @endforeach
        </aside>

    </div>
</div>

@stop

@section('scripts')
{{-- styles live here (outside #app) so Vue's mount doesn't strip the <style> --}}
<style>
    .fb-sub-detail {
        display: grid;
        grid-template-columns: 1fr 340px;
        gap: 24px;
        align-items: start;
        margin-top: 24px;
    }
    @media (max-width: 980px) { .fb-sub-detail { grid-template-columns: 1fr; } }

    /* the .block header sits flush; add breathing room before the content */
    .fb-sub-detail .block > header { margin-bottom: 16px; }
    /* the grid gap handles column spacing, so reset .block's own top margin */
    .fb-sub-detail .block { margin-top: 0; }
    .fb-sub-side .block + .block { margin-top: 24px; }
    .fb-sub-side__heading {
        margin: 0 0 14px;
        font-size: 13px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .04em;
        color: #6b7280;
    }

    .fb-sub-fields { margin: 0; }
    .fb-sub-fields__row {
        display: grid;
        grid-template-columns: 220px 1fr;
        gap: 20px;
        padding: 12px 0;
        border-bottom: 1px solid #eef0f2;
    }
    .fb-sub-fields__row:last-child { border-bottom: 0; }
    .fb-sub-fields dt { margin: 0; font-weight: 600; color: #374151; }
    .fb-sub-fields dd { margin: 0; color: #111827; word-break: break-word; }

    .fb-sub-meta { margin: 0; }
    .fb-sub-meta__row { display: flex; gap: 12px; padding: 6px 0; font-size: 13px; }
    .fb-sub-meta dt { flex: 0 0 76px; margin: 0; color: #9ca3af; font-weight: 600; }
    .fb-sub-meta dd { margin: 0; color: #374151; word-break: break-word; }
    .fb-sub-muted { color: #c4c8cf; }
</style>
@stop
