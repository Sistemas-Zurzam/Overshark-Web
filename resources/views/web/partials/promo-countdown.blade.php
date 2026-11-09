{{-- Cuenta regresiva de una promoción por horas (resources/js/promo-countdown.js). $promo = arreglo de App\Support\Promotions. --}}
<span {{ $attributes ?? '' }} class="{{ $class ?? '' }}">{{ $prefix ?? 'Termina en ' }}<time data-countdown="{{ $promo['ends_at']->toIso8601String() }}" datetime="{{ $promo['ends_at']->toIso8601String() }}" class="tabular-nums">--:--:--</time></span>
