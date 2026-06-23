@extends('core::layouts.master')

@section('title', $heading.' — Submissions')

@section('template')

<style>
    .fb-sub-row { cursor: pointer; }
    .fb-sub-row:hover { background: #f6f7f9; }
    .fb-sub-row__summary { color: #555; }
    .fb-sub-empty { padding: 30px; text-align: center; color: #888; }
</style>


<div class="app__content">

    <div class="app__content-header">
        <h2>
            <a href="{{ $backRoute }}">{{ $heading }}</a> /
            <span>{{ $form->name }} — Submissions</span>
        </h2>
        <aside>
            <a href="{{ $backRoute }}" class="button button--grey">Back to Forms</a>
            <a href="{{ route('refined.form-builder.export', $form) }}" class="button button--blue">Export CSV</a>
        </aside>
    </div>

    <div class="block">
        @if ($submissions->count())
            <div class="data-table">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Submitted</th>
                            <th>Notifications</th>
                            <th>Summary</th>
                            <th class="text--right"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($submissions as $sub)
                            @php $url = route('refined.form-builder.submissions.show', [$form, $sub->token]); @endphp
                            <tr class="fb-sub-row" onclick="window.location='{{ $url }}'">
                                <td>{{ $sub->created_at->format(config('form-builder.datetime_format', 'd/m/Y g:ia')) }}</td>
                                <td>{{ $sub->count }} sent</td>
                                <td class="fb-sub-row__summary">{{ \Illuminate\Support\Str::limit($sub->summary, 80) ?: '—' }}</td>
                                <td class="text--right">
                                    <a href="{{ $url }}" class="button button--small button--blue">View</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p class="fb-sub-empty">No submissions yet for this form.</p>
        @endif
    </div>

</div>

@stop
