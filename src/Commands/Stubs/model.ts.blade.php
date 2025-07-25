import { generateUuid } from "@/helpers/generateUuid";

export type {{ $modelName }} = {
@foreach ($properties as $name => $type)
    {{ $name }}: {!! $type !!},
@endforeach
}

export const {{ $modelName }}Defaults = {
@foreach( $defaults as $name => $val)
    {{ $name }}: {!! $val !!},
@endforeach
} as {{ $modelName }};

export function make{{ $modelName }}(attributes: Partial<{{ $modelName }}>): {{ $modelName }} {
    return {
        ...{{ $modelName }}Defaults,
        id: generateUuid(),
        ...attributes,
    } as {{ $modelName }};
}
