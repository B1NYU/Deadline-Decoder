export type DocumentStatus = 'pending' | 'processing' | 'completed' | 'failed';

export type DocumentType = 'text' | 'pdf';

export type DocumentItem = {
    id: number;
    title: string;
    type: DocumentType;
    status: DocumentStatus;
    error_message: string | null;
    page_count: number | null;
    chunk_count?: number;
    char_count: number | null;
    created_at: string | null;
};

export type DocumentChunk = {
    id: number;
    chunk_index: number;
    page: number | null;
    section: string | null;
    content: string;
};

export type DocumentLimits = {
    maxTextChars: number;
    minTextChars: number;
    maxPdfMb: number;
};
