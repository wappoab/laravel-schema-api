import { defineCollectionStore } from "@/stores/defineCollectionStore.ts";
import type { {{ $modelName }} } from "@/models";

export const use{{ $modelName }}Store = defineCollectionStore("{{ $collectionName }}");
