export type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

/**
 * Shape of a Laravel LengthAwarePaginator serialized to JSON.
 */
export type Paginated<T> = {
    data: T[];
    links: PaginationLink[];
    current_page: number;
    last_page: number;
    per_page: number;
    from: number | null;
    to: number | null;
    total: number;
};
