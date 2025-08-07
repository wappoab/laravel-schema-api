import { defineCollectionStore } from "@/stores/defineCollectionStore.ts";
import type { User } from "@/models";

export const useUserStore = defineCollectionStore("users");
