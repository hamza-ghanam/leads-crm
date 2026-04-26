<ul class="mb-0 pl-3">
    @foreach($items as $key => $value)
        <li>
            @if(is_array($value))
                @if(!is_numeric($key))
                    <strong>{{ $key }}</strong>:
                @endif
                @include('tickets.partials.nested-list', ['items' => $value])
            @else
                @if(!is_numeric($key))
                    <strong>{{ $key }}</strong>:
                @endif
                {{ $value }}
            @endif
        </li>
    @endforeach
</ul>
