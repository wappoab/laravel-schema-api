import { defineStore } from "pinia";
import { computed } from "vue";
import { type CollectionNameEnum, CollectionName } from "@/enums/CollectionName.ts";
import type { CollectionDocumentBase } from "sylviejs/database/collection/collection-document-base";

import { useCategoryStore } from "@/stores/collections/categories.ts";
import { useCategoryPostStore } from "@/stores/collections/category_posts.ts";
import { usePostStore } from "@/stores/collections/posts.ts";
import { useSecretStore } from "@/stores/collections/secrets.ts";
import { useUserStore } from "@/stores/collections/users.ts";

import { type Category } from "@/models";
import { type CategoryPost } from "@/models";
import { type Post } from "@/models";
import { type Secret } from "@/models";
import { type User } from "@/models";

/*
export interface CollectionStoreMap {
    categories: ReturnType<typeof useCategoryStore>;
    category_posts: ReturnType<typeof useCategoryPostStore>;
    posts: ReturnType<typeof usePostStore>;
    secrets: ReturnType<typeof useSecretStore>;
    users: ReturnType<typeof useUserStore>;
}
*/

export interface ModelMap {
    categories: {
        store: ReturnType<typeof useCategoryStore>,
        model: Category,
        document: Category & CollectionDocumentBase,
    }
    category_posts: {
        store: ReturnType<typeof useCategoryPostStore>,
        model: CategoryPost,
        document: CategoryPost & CollectionDocumentBase,
    }
    posts: {
        store: ReturnType<typeof usePostStore>,
        model: Post,
        document: Post & CollectionDocumentBase,
    }
    secrets: {
        store: ReturnType<typeof useSecretStore>,
        model: Secret,
        document: Secret & CollectionDocumentBase,
    }
    users: {
        store: ReturnType<typeof useUserStore>,
        model: User,
        document: User & CollectionDocumentBase,
    }
}

export const useCollectionStore = defineStore("collections", () => {
    const categoryStore = useCategoryStore();
    const categoryPostStore = useCategoryPostStore();
    const postStore = usePostStore();
    const secretStore = useSecretStore();
    const userStore = useUserStore();

    const storeMap: Record<keyof ModelMap, any> = {
            categories: categoryStore,
            category_posts: categoryPostStore,
            posts: postStore,
            secrets: secretStore,
            users: userStore,
        };

    const getStore = <K extends keyof ModelMap>(collectionName: K): ModelMap[K]["store"] => {
        return storeMap[collectionName]! as ModelMap[K]["store"];
    };

    const changesCount = computed(() => {
        let changes = 0;
        changes += categoryStore.changesCount;
        changes += categoryPostStore.changesCount;
        changes += postStore.changesCount;
        changes += secretStore.changesCount;
        changes += userStore.changesCount;
        return changes;
    });

    const flushChanges = () => {
        categoryStore.flushChanges();
        categoryPostStore.flushChanges();
        postStore.flushChanges();
        secretStore.flushChanges();
        userStore.flushChanges();
    }

    const loadRecordsIfNeeded = () => {
            categoryStore.loadRecordsIfNeeded();
            categoryPostStore.loadRecordsIfNeeded();
            postStore.loadRecordsIfNeeded();
            secretStore.loadRecordsIfNeeded();
            userStore.loadRecordsIfNeeded();
        }

    return {
        getStore,
        loadRecordsIfNeeded,
        changesCount,
        flushChanges,
    };
});

