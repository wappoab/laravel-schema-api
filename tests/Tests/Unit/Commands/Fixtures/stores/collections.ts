import { defineStore } from "pinia";
import { computed } from "vue";
import { type CollectionNameEnum, CollectionName } from "@/enums/CollectionName.ts";
import type { CollectionDocumentBase } from "sylviejs/database/collection/collection-document-base";

import { useCategoryStore } from "@/stores/collections/categories.ts";
import { useCategoryPostStore } from "@/stores/collections/category_posts.ts";
import { useDataTypeStore } from "@/stores/collections/data_types.ts";
import { useOrderRowEntryStore } from "@/stores/collections/order_row_entries.ts";
import { useOrderRowStore } from "@/stores/collections/order_rows.ts";
import { useOrderStore } from "@/stores/collections/orders.ts";
import { usePostStore } from "@/stores/collections/posts.ts";
import { useSecretStore } from "@/stores/collections/secrets.ts";
import { useUserStore } from "@/stores/collections/users.ts";

import { type Category } from "@/models";
import { type CategoryPost } from "@/models";
import { type DataType } from "@/models";
import { type OrderRowEntry } from "@/models";
import { type OrderRow } from "@/models";
import { type Order } from "@/models";
import { type Post } from "@/models";
import { type Secret } from "@/models";
import { type User } from "@/models";

/*
export interface CollectionStoreMap {
    categories: ReturnType<typeof useCategoryStore>;
    category_posts: ReturnType<typeof useCategoryPostStore>;
    data_types: ReturnType<typeof useDataTypeStore>;
    order_row_entries: ReturnType<typeof useOrderRowEntryStore>;
    order_rows: ReturnType<typeof useOrderRowStore>;
    orders: ReturnType<typeof useOrderStore>;
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
    data_types: {
        store: ReturnType<typeof useDataTypeStore>,
        model: DataType,
        document: DataType & CollectionDocumentBase,
    }
    order_row_entries: {
        store: ReturnType<typeof useOrderRowEntryStore>,
        model: OrderRowEntry,
        document: OrderRowEntry & CollectionDocumentBase,
    }
    order_rows: {
        store: ReturnType<typeof useOrderRowStore>,
        model: OrderRow,
        document: OrderRow & CollectionDocumentBase,
    }
    orders: {
        store: ReturnType<typeof useOrderStore>,
        model: Order,
        document: Order & CollectionDocumentBase,
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
    const dataTypeStore = useDataTypeStore();
    const orderRowEntryStore = useOrderRowEntryStore();
    const orderRowStore = useOrderRowStore();
    const orderStore = useOrderStore();
    const postStore = usePostStore();
    const secretStore = useSecretStore();
    const userStore = useUserStore();

    const storeMap: Record<keyof ModelMap, any> = {
            categories: categoryStore,
            category_posts: categoryPostStore,
            data_types: dataTypeStore,
            order_row_entries: orderRowEntryStore,
            order_rows: orderRowStore,
            orders: orderStore,
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
        changes += dataTypeStore.changesCount;
        changes += orderRowEntryStore.changesCount;
        changes += orderRowStore.changesCount;
        changes += orderStore.changesCount;
        changes += postStore.changesCount;
        changes += secretStore.changesCount;
        changes += userStore.changesCount;
        return changes;
    });

    const flushChanges = () => {
        categoryStore.flushChanges();
        categoryPostStore.flushChanges();
        dataTypeStore.flushChanges();
        orderRowEntryStore.flushChanges();
        orderRowStore.flushChanges();
        orderStore.flushChanges();
        postStore.flushChanges();
        secretStore.flushChanges();
        userStore.flushChanges();
    }

    const loadRecordsIfNeeded = () => {
            categoryStore.loadRecordsIfNeeded();
            categoryPostStore.loadRecordsIfNeeded();
            dataTypeStore.loadRecordsIfNeeded();
            orderRowEntryStore.loadRecordsIfNeeded();
            orderRowStore.loadRecordsIfNeeded();
            orderStore.loadRecordsIfNeeded();
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

