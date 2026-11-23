import { Badge } from '@/components/ui/badge';
import type { ProjectStatus } from '@/types/project';

const statusMeta: Record<
    ProjectStatus,
    { label: string; variant: 'default' | 'secondary' | 'outline' | 'destructive' }
> = {
    draft: { label: 'Draf', variant: 'outline' },
    analyzing: { label: 'Sedang dianalisis', variant: 'secondary' },
    ready: { label: 'Siap ditinjau', variant: 'default' },
    failed: { label: 'Gagal diproses', variant: 'destructive' },
};

export function ProjectStatusBadge({ status }: { status: ProjectStatus }) {
    const meta = statusMeta[status];

    return <Badge variant={meta.variant}>{meta.label}</Badge>;
}
