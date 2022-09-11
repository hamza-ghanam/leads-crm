@component('mail::message')
# {{ $emails['title'] }}

{{ $emails['message'] }} {{ $emails['user'] }}

@component('mail::button', ['url' =>  route('tickets.show', [$emails['ticket']])])
More Details
@endcomponent
Regards,<br>
{{ config('app.name') }}
@endcomponent
