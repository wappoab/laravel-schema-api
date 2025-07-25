import { type App as AppType } from 'vue';

@foreach( $collections as $collection )
import {{ $collection['modelName'] }}Form from "@/components/forms/{{ $collection['modelName'] }}Form.vue";
@endforeach

export function registerFormComponents(app: AppType): void {
@foreach( $collections as $collection )
    app.component("{{ $collection['modelName'] }}Form", {{ $collection['modelName'] }}Form);
    app.component("{{ $collection['collectionName'] }}", {{ $collection['modelName'] }}Form);
    app.component({!! json_encode($collection['morphAlias']) !!}, {{ $collection['modelName'] }}Form);
@endforeach
}
