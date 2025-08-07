import { defineCollectionStore } from "@/stores/defineCollectionStore.ts";
import type { Secret } from "@/models";

export const useSecretStore = defineCollectionStore("secrets");
