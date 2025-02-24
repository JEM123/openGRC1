<div class="prose max-w-none">
    <div class="space-y-4">
        @php
            $propertiesArray = is_string($properties) ? json_decode($properties, true) : ($properties ?? []);
        @endphp
        
        @foreach($propertiesArray as $key => $value)
            <div class="grid grid-cols-2 gap-4">
                <div class="font-medium">{{ ucwords(str_replace('_', ' ', $key)) }}</div>
                <div>
                    @if(is_array($value) || is_object($value))
                        <pre class="whitespace-pre-wrap">{{ json_encode($value, JSON_PRETTY_PRINT) }}</pre>
                    @else
                        {{ $value }}
                    @endif
                </div>
            </div>
        @endforeach
    </div>
</div> 