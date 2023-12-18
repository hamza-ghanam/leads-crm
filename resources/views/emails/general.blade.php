@component('mail::message')
# {{ $emails['title'] }}

{{ $emails['message'] }}

@component('mail::panel')
    Please user the following username: <br/><strong>{{ $emails['user']->email }}</strong> <br/><br/>
    And the password that provided by your administrator.
@endcomponent

@component('mail::button', ['url' =>  route('login')])
    Click to Login
@endcomponent

Regards,<br>
{{ config('app.name') }}
@endcomponent
