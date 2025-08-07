import { generateUuid } from "@/helpers/generateUuid";

export type Secret = {
    id: string | null,
    launch_code: string | null,
    nuke_payload: string | null,
    created_at: Date | null,
    updated_at: Date | null,
}

export const SecretDefaults = {
    id: null,
    launch_code: null,
    nuke_payload: null,
    created_at: null,
    updated_at: null,
} as Secret;

export function makeSecret(attributes: Partial<Secret>): Secret {
    return {
        ...SecretDefaults,
        id: generateUuid(),
        ...attributes,
    } as Secret;
}
