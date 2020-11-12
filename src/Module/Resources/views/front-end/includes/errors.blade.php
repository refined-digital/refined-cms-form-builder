@if (isset($errors) && count($errors) > 0)
    <div class="alert-holder">
        <div class="alert alert--error">
            <h4>You have some errors in your form.</h4>
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    </div>
@endif
