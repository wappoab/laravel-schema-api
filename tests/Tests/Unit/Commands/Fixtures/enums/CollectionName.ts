export enum CollectionName {
    Category = "categories",
    CategoryPost = "category_posts",
    DataType = "data_types",
    OrderLink = "order_links",
    OrderRowEntry = "order_row_entries",
    OrderRow = "order_rows",
    Order = "orders",
    Post = "posts",
    Secret = "secrets",
    User = "users",
}

export type CollectionNameEnum = `${ CollectionName }`;
