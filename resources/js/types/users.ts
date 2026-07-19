export type ManagedUserRole =
    | 'admin'
    | 'productmanager'
    | 'editor'
    | 'lector'
    | 'author';

export type ManagedUser = {
    id: number;
    name: string;
    email: string;
    role: ManagedUserRole;
    created_at: string;
    updated_at: string;
};

export type UserRoleOption = {
    value: ManagedUserRole;
    label: string;
};

export type PaginatedUsers = {
    data: ManagedUser[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    links: Array<{
        url: string | null;
        label: string;
        active: boolean;
    }>;
};
