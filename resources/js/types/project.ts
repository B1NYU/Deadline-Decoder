export type ProjectStatus = 'draft' | 'analyzing' | 'ready' | 'failed';

export type Project = {
    id: number;
    title: string;
    description: string | null;
    status: ProjectStatus;
    created_at: string | null;
};

export type ProjectSummary = Pick<
    Project,
    'id' | 'title' | 'status' | 'created_at'
>;

export type Paginated<T> = {
    data: T[];
    current_page: number;
    last_page: number;
    total: number;
    prev_page_url: string | null;
    next_page_url: string | null;
};
