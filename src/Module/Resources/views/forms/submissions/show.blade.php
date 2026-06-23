@extends('core::layouts.master')

@section('title', $heading.' — Submission')

@section('template')

<style>
    .fb-sub-detail { display: grid; grid-template-columns: 1fr 320px; gap: 20px; align-items: start; }
    @media (max-width: 900px) { .fb-sub-detail { grid-template-columns: 1fr; } }
    .fb-sub-detail__heading { margin: 0 0 16px; font-size: 16px; }
    .fb-sub-fields__row { display: grid; grid-template-columns: 200px 1fr; gap: 16px; padding: 10px 0; border-bottom: 1px solid #eee; }
    .fb-sub-fields__row:last-child { border-bottom: 0; }
    .fb-sub-fields dt { font-weight: 600; color: #333; }
    .fb-sub-fields dd { margin: 0; color: #222; }
    .fb-sub-note { margin-bottom: 16px; }
    .fb-sub-note__title { margin: 0 0 12px; font-size: 14px; padding-bottom: 8px; border-bottom: 1px solid #eee; }
    .fb-sub-meta > div { display: flex; gap: 10px; padding: 5px 0; font-size: 13px; }
    .fb-sub-meta dt { flex: 0 0 70px; color: #888; font-weight: 600; }
    .fb-sub-meta dd { margin: 0; word-break: break-word; }
    .fb-sub-muted { color: #aaa; }
</style>


<div class="app__content">

    <div class="app__content-header">
        <h2>
            <a href="{{ route('refined.form-builder.index') }}">{{ $heading }}</a> /
            <a href="{{ $backRoute }}">{{ $form->name }} — Submissions</a> /
            <span>{{ $submission->created_at->format(config('form-builder.datetime_format', 'd/m/Y g:ia')) }}</span>
        </h2>
        <aside>
            <a href="{{ $backRoute }}" class="button button--grey">Back to Submissions</a>
        </aside>
    </div>

    <div class="fb-sub-detail">

        {{-- left: the submitted form values --}}
        <div class="fb-sub-detail__main">
            <div class="block">
                <h3 class="fb-sub-detail__heading">Submission</h3>

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
        <aside class="fb-sub-detail__side">
            @foreach ($submission->notifications as $note)
                <div class="block fb-sub-note">
                    <h4 class="fb-sub-note__title">
                        {{ $note->name ?: 'Notification' }}
                    </h4>

                    <dl class="fb-sub-meta">
                        <div><dt>Sent</dt><dd>{{ $note->created_at->format(config('form-builder.datetime_format', 'd/m/Y g:ia')) }}</dd></div>
                        @if ($note->subject)<div><dt>Subject</dt><dd>{{ $note->subject }}</dd></div>@endif
                        <div><dt>To</dt><dd>{{ $note->to ?: '—' }}</dd></div>
                        @if ($note->cc)<div><dt>CC</dt><dd>{{ $note->cc }}</dd></div>@endif
                        @if ($note->bcc)<div><dt>BCC</dt><dd>{{ $note->bcc }}</dd></div>@endif
                        @if ($note->reply_to)<div><dt>Reply-To</dt><dd>{{ $note->reply_to }}</dd></div>@endif
                        @if ($note->from)<div><dt>From</dt><dd>{{ $note->from }}</dd></div>@endif
                        <div><dt>IP</dt><dd>{{ $note->ip ?: '—' }}</dd></div>
                    </dl>
                </div>
            @endforeach
        </aside>

    </div>

</div>

@stop
