@php
  $gateways = paymentGateways()->getAll();
@endphp

@if (sizeof($gateways))
  <div class="form__row form__row--payment-gateway">
    @foreach ($gateways as $type => $gateway)
      <div class="form__payment-gateway payment-gateway{{ $loop->first ? ' payment-gateway--active' : '' }} form__payment-gateway--{{ str_slug($type) }}">
        <input type="radio" name="payment_gateway" id="form-payment-gateway-{{ str_slug($type) }}" value="{{str_slug($type)}}"{!! $loop->first ? ' checked' : '' !!} onchange="FormBuilder.paymentGatewayChanged(event)"/>
        <label class="form__label" for="form-payment-gateway-{{ str_slug($type) }}">{{ $type }}</label>
        <div class="payment-gateway__details">
          {!! view()->make($gateway->getView())->with(compact('form')) !!}
        </div>
      </div>
    @endforeach
  </div>
@endif
