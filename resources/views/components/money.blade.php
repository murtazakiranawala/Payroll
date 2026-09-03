{{--
    Central currency formatting so every rupee amount in the app renders
    consistently (same ₹ symbol placement, same decimal formatting) instead
    of some views prefixing "₹" and others showing bare number_format().

    Usage: <x-money :value="$totalEarnings" />
           <x-money :value="$item->net_pay" :symbol="false" /> (dense tables where the column header already says "amounts in Rs.")
--}}
@props(['value', 'decimals' => 2, 'symbol' => true])
@php
    $amount = number_format((float) ($value ?? 0), $decimals);
    $negative = (float) ($value ?? 0) < 0;
@endphp
<span {{ $attributes->merge(['class' => 'money' . ($negative ? ' text-danger' : '')]) }}>@if($symbol)<span class="money-symbol">&#8377;</span> @endif{{ $amount }}</span>
