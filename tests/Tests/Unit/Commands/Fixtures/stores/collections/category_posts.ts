import { defineCollectionStore } from "@/stores/defineCollectionStore.ts";
import type { CategoryPost } from "@/models";

export const useCategoryPostStore = defineCollectionStore("category_posts");
