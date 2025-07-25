export enum {{ $name }} {
@foreach($cases as $key => $value)
    {{ $key }} = {!! json_encode($value) !!},
@endforeach
}

export type {{ $name }}Enum = `${ {{ $name }} }`;
