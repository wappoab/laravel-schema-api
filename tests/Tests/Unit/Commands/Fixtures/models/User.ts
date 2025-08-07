import { generateUuid } from "@/helpers/generateUuid";

export type User = {
    id: string | null,
    name: string | null,
    email: string | null,
    email_verified_at: Date | null,
    password: string | null,
    remember_token: string | null,
    created_at: Date | null,
    updated_at: Date | null,
}

export const UserDefaults = {
    id: null,
    name: null,
    email: null,
    email_verified_at: null,
    password: null,
    remember_token: null,
    created_at: null,
    updated_at: null,
} as User;

export function makeUser(attributes: Partial<User>): User {
    return {
        ...UserDefaults,
        id: generateUuid(),
        ...attributes,
    } as User;
}
