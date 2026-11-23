import { Form, Link } from '@inertiajs/react';
import { FileText, Trash2 } from 'lucide-react';
import {
    destroy,
    show,
} from '@/actions/App/Http/Controllers/DocumentController';
import { DocumentStatusBadge } from '@/components/document-status-badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Spinner } from '@/components/ui/spinner';
import { formatDate } from '@/lib/format-date';
import type { DocumentItem } from '@/types/document';

type Props = {
    projectId: number;
    documents: DocumentItem[];
};

function describe(document: DocumentItem): string {
    const parts = [document.type === 'pdf' ? 'PDF' : 'Teks'];

    if (document.status === 'completed') {
        if (document.page_count !== null) {
            parts.push(`${document.page_count} halaman berisi teks`);
        }

        if (document.chunk_count !== undefined) {
            parts.push(`${document.chunk_count} potongan teks`);
        }
    }

    parts.push(`ditambahkan ${formatDate(document.created_at)}`);

    return parts.join(' · ');
}

export function DocumentList({ projectId, documents }: Props) {
    if (documents.length === 0) {
        return (
            <div className="flex flex-col items-center gap-2 rounded-xl border border-dashed border-border p-8 text-center">
                <FileText className="size-8 text-muted-foreground" />
                <p className="font-medium">Belum ada dokumen</p>
                <p className="max-w-sm text-sm text-muted-foreground">
                    Tambahkan teks instruksi atau PDF di atas. Teksnya akan
                    diekstrak dan dipotong agar bisa ditelusuri sumbernya.
                </p>
            </div>
        );
    }

    return (
        <ul className="divide-y divide-border rounded-xl border border-border bg-card">
            {documents.map((document) => (
                <li
                    key={document.id}
                    className="flex flex-wrap items-center justify-between gap-3 p-4"
                >
                    <div className="min-w-0">
                        <p className="truncate font-medium">{document.title}</p>
                        <p className="text-xs text-muted-foreground">
                            {describe(document)}
                        </p>
                        {document.status === 'failed' &&
                            document.error_message && (
                                <p
                                    role="alert"
                                    className="mt-1 text-sm text-destructive"
                                >
                                    {document.error_message}
                                </p>
                            )}
                    </div>

                    <div className="flex items-center gap-2">
                        <DocumentStatusBadge status={document.status} />

                        {document.status === 'completed' && (
                            <Button variant="outline" size="sm" asChild>
                                <Link
                                    href={show({
                                        project: projectId,
                                        document: document.id,
                                    })}
                                >
                                    Lihat teks
                                </Link>
                            </Button>
                        )}

                        <Dialog>
                            <DialogTrigger asChild>
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    aria-label={`Hapus ${document.title}`}
                                >
                                    <Trash2 />
                                </Button>
                            </DialogTrigger>
                            <DialogContent>
                                <DialogHeader>
                                    <DialogTitle>
                                        Hapus dokumen ini?
                                    </DialogTitle>
                                    <DialogDescription>
                                        "{document.title}" dan teks hasil
                                        ekstraksinya akan dihapus permanen.
                                    </DialogDescription>
                                </DialogHeader>
                                <Form
                                    {...destroy.form({
                                        project: projectId,
                                        document: document.id,
                                    })}
                                >
                                    {({ processing }) => (
                                        <DialogFooter className="gap-2">
                                            <DialogClose asChild>
                                                <Button
                                                    type="button"
                                                    variant="secondary"
                                                >
                                                    Batal
                                                </Button>
                                            </DialogClose>
                                            <Button
                                                type="submit"
                                                variant="destructive"
                                                disabled={processing}
                                            >
                                                {processing && <Spinner />}
                                                Ya, hapus
                                            </Button>
                                        </DialogFooter>
                                    )}
                                </Form>
                            </DialogContent>
                        </Dialog>
                    </div>
                </li>
            ))}
        </ul>
    );
}
