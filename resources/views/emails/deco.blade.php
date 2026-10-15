@component('mail::message')
# Congratulations {{ $username }}! {{ $artist }} decorated you.

You have bought 10 songs from {{ $artist }}, so you just earned a deco. Thank you for supporting Kenyan artists.

@component('mail::button', ['url' => url('/profile/' . $artist . '/view')])
VISIT {{ $artist }}
@endcomponent

Thanks,<br>
{{ config('app.name') }}
@endcomponent
