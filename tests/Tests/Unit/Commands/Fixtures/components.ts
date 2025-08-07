import { type App as AppType } from 'vue';

import CategoryForm from "@/components/forms/CategoryForm.vue";
import CategoryPostForm from "@/components/forms/CategoryPostForm.vue";
import PostForm from "@/components/forms/PostForm.vue";
import SecretForm from "@/components/forms/SecretForm.vue";
import UserForm from "@/components/forms/UserForm.vue";

export function registerFormComponents(app: AppType): void {
    app.component("CategoryForm", CategoryForm);
    app.component("categories", CategoryForm);
    app.component("Wappo\\LaravelSchemaApi\\Tests\\Fakes\\Models\\Category", CategoryForm);
    app.component("CategoryPostForm", CategoryPostForm);
    app.component("category_post", CategoryPostForm);
    app.component("Wappo\\LaravelSchemaApi\\Tests\\Fakes\\Models\\CategoryPost", CategoryPostForm);
    app.component("PostForm", PostForm);
    app.component("posts", PostForm);
    app.component("Wappo\\LaravelSchemaApi\\Tests\\Fakes\\Models\\Post", PostForm);
    app.component("SecretForm", SecretForm);
    app.component("secrets", SecretForm);
    app.component("Wappo\\LaravelSchemaApi\\Tests\\Fakes\\Models\\Secret", SecretForm);
    app.component("UserForm", UserForm);
    app.component("users", UserForm);
    app.component("Wappo\\LaravelSchemaApi\\Tests\\Fakes\\Models\\User", UserForm);
}
