@props(['value' => null, 'format' => 'Y/m/d'])

@php
    use App\Support\JDate;

    $out = '';
    if (! empty($value)) {
        try {
            $carbon = $value instanceof \Carbon\Carbon ? $value : \Carbon\Carbon::parse($value);
            $out = JDate::toPersianDigits(\Morilog\Jalali\Jalalian::fromCarbon($carbon)->format($format));
        } catch (\Throwable) {
            $out = '';
        }
    }
@endphp<span {{ $attributes }}>{{ $out !== '' ? $out : '—' }}</span>
