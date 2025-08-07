import { generateUuid } from "@/helpers/generateUuid";

export type Category = {
    id: string | null,
    name: string | null,
    slug: string | null,
    author_id: string | null,
    created_at: Date | null,
    updated_at: Date | null,
    deleted_at: Date | null,
}

export const CategoryDefaults = {
    id: null,
    name: null,
    slug: null,
    author_id: null,
    created_at: null,
    updated_at: null,
    deleted_at: null,
} as Category;

export function makeCategory(attributes: Partial<Category>): Category {
    return {
        ...CategoryDefaults,
        id: generateUuid(),
        ...attributes,
    } as Category;
}
