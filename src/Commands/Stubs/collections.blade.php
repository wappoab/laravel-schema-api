import { defineStore } from "pinia";
import { computed } from "vue";
import { type CollectionNameEnum, CollectionName } from "@/enums/CollectionName.ts";
import type { CollectionDocumentBase } from "sylviejs/database/collection/collection-document-base";

@foreach($collections as $collection)
import { use{{ $collection['modelName'] }}Store } from "@/stores/collections/{{ $collection['collectionName'] }}.ts";
@endforeach

@foreach( $collections as $collection )
import { type {{ $collection['modelName'] }} } from "@/models";
@endforeach

/*
export interface CollectionStoreMap {
@foreach($collections as $collection)
    {{ $collection['collectionName'] }}: ReturnType<typeof use{{ $collection['modelName'] }}Store>;
@endforeach
}
*/

export interface ModelMap {
@foreach($collections as $collection)
    {{ $collection['collectionName'] }}: {
        store: ReturnType<typeof use{{ $collection['modelName'] }}Store>,
        model: {{ $collection['modelName'] }},
        document: {{ $collection['modelName'] }} & CollectionDocumentBase,
    }
@endforeach
}

export const useCollectionStore = defineStore("collections", () => {
@foreach($collections as $collection)
    const {{ lcfirst($collection['modelName']) }}Store = use{{ $collection['modelName'] }}Store();
@endforeach

    const storeMap: Record<keyof ModelMap, any> = {
    @foreach($collections as $collection)
        {{ $collection['collectionName'] }}: {{ lcfirst($collection['modelName']) }}Store,
    @endforeach
    };

    const getStore = <K extends keyof ModelMap>(collectionName: K): ModelMap[K]["store"] => {
        return storeMap[collectionName]! as ModelMap[K]["store"];
    };

    const changesCount = computed(() => {
        let changes = 0;
@foreach($collections as $collection)
        changes += {{ lcfirst($collection['modelName']) }}Store.changesCount;
@endforeach
        return changes;
    });

    const flushChanges = () => {
@foreach($collections as $collection)
        {{ lcfirst($collection['modelName']) }}Store.flushChanges();
@endforeach
    }

    const loadRecordsIfNeeded = () => {
    @foreach($collections as $collection)
        {{ lcfirst($collection['modelName']) }}Store.loadRecordsIfNeeded();
    @endforeach
    }

    return {
        getStore,
        loadRecordsIfNeeded,
        changesCount,
        flushChanges,
    };
});

