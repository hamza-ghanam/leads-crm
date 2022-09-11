@component('mail::message')
# {{ $emails['title'] }}

{{ $emails['message'] }}

Regards,<br>
{{ config('app.name') }}
@endcomponent
