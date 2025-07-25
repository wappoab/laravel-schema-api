@foreach($collections as $collection)
export { type {{ $collection['modelName'] }}, make{{ $collection['modelName'] }}, {{ $collection['modelName'] }}Defaults } from "./{{ $collection['modelName'] }}.ts";
import { type {{ $collection['modelName'] }}, make{{ $collection['modelName'] }} } from "./{{ $collection['modelName'] }}";
@endforeach

import type { ModelMap } from "@/stores/collections.ts";

export const collectionMap: Record<keyof ModelMap, any> = {
@foreach($collections as $collection)
    {{ $collection['collectionName'] }}: make{{ $collection['modelName'] }},
@endforeach
}

export function makeModel<K extends keyof ModelMap>(modelName: K, attributes: Partial<ModelMap[K]["document"]>): ModelMap[K]["document"] {
    return collectionMap[modelName]!(attributes) as ModelMap[K]["document"];
}
