import { defineCollectionStore } from "@/stores/defineCollectionStore.ts";
import type { Category } from "@/models";

export const useCategoryStore = defineCollectionStore("categories");
