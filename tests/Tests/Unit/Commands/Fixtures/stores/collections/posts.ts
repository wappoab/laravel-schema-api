import { defineCollectionStore } from "@/stores/defineCollectionStore.ts";
import type { Post } from "@/models";

export const usePostStore = defineCollectionStore("posts");
