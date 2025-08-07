import { generateUuid } from "@/helpers/generateUuid";

export type Post = {
    id: string | null,
    title: string | null,
    slug: string | null,
    status: string | null,
    content: string | null,
    author_id: string | null,
    created_at: Date | null,
    updated_at: Date | null,
    deleted_at: Date | null,
}

export const PostDefaults = {
    id: null,
    title: null,
    slug: null,
    status: null,
    content: null,
    author_id: null,
    created_at: null,
    updated_at: null,
    deleted_at: null,
} as Post;

export function makePost(attributes: Partial<Post>): Post {
    return {
        ...PostDefaults,
        id: generateUuid(),
        ...attributes,
    } as Post;
}
