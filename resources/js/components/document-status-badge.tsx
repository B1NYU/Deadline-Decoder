import { Badge } from '@/components/ui/badge';
import type { DocumentStatus } from '@/types/document';

const statusMeta: Record<
    DocumentStatus,
    {
        label: string;
        variant: 'default' | 'secondary' | 'outline' | 'destructive';
    }
> = {
    pending: { label: 'Menunggu', variant: 'outline' },
    processing: { label: 'Diproses', variant: 'secondary' },
    completed: { label: 'Teks siap', variant: 'default' },
    failed: { label: 'Gagal', variant: 'destructive' },
};

export function DocumentStatusBadge({ status }: { status: DocumentStatus }) {
    const meta = statusMeta[status];

    return <Badge variant={meta.variant}>{meta.label}</Badge>;
}
