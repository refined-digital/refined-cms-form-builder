@php
  $gateways = [];
  if (config('products.orders.active')) {
    $gateways = paymentGateways()->getAll();
  }
@endphp

@if (sizeof($gateways))
  <div class="form__row form__row--payment-gateway">
    @foreach ($gateways as $type => $gateway)
      <div class="form__payment-gateway payment-gateway{{ $loop->first ? ' payment-gateway--active' : '' }} form__payment-gateway--{{ Str::slug($type) }}">
        @if (sizeof($gateways) > 1)
          <input type="radio" name="payment_gateway" id="form-payment-gateway-{{ Str::slug($type) }}" value="{{Str::slug($type)}}"{!! $loop->first ? ' checked' : '' !!}/>
          <label class="form__label" for="form-payment-gateway-{{ Str::slug($type) }}">{{ $type }}</label>
        @else
          <input type="hidden" name="payment_gateway" value="{{Str::slug($type)}}"/>
        @endif
        <div class="payment-gateway__details">
          {!! view()->make($gateway->getView())->with(compact('form')) !!}
        </div>
      </div>
    @endforeach
  </div>
@endif
