import { generateUuid } from "@/helpers/generateUuid";

export type CategoryPost = {
    category_id: string | null,
    post_id: string | null,
}

export const CategoryPostDefaults = {
    category_id: null,
    post_id: null,
} as CategoryPost;

export function makeCategoryPost(attributes: Partial<CategoryPost>): CategoryPost {
    return {
        ...CategoryPostDefaults,
        id: generateUuid(),
        ...attributes,
    } as CategoryPost;
}
