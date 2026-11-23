import { Head, Link } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import { DocumentStatusBadge } from '@/components/document-status-badge';
import { Button } from '@/components/ui/button';
import { index, show as showProject } from '@/routes/projects';
import type { DocumentChunk, DocumentItem } from '@/types/document';

type Props = {
    project: { id: number; title: string };
    document: DocumentItem;
    chunks: DocumentChunk[];
};

export default function DocumentsShow({ project, document, chunks }: Props) {
    const total = document.chunk_count ?? chunks.length;

    return (
        <>
            <Head title={document.title} />
            <div className="mx-auto flex w-full max-w-4xl flex-col gap-6 p-4 md:p-8">
                <div>
                    <Button variant="ghost" size="sm" asChild>
                        <Link href={showProject(project.id)}>
                            <ArrowLeft /> {project.title}
                        </Link>
                    </Button>
                </div>

                <header>
                    <div className="mb-2">
                        <DocumentStatusBadge status={document.status} />
                    </div>
                    <h1 className="text-2xl font-semibold tracking-tight text-balance">
                        {document.title}
                    </h1>
                    <p className="mt-1 text-sm text-muted-foreground">
                        Teks hasil ekstraksi. Bandingkan dengan dokumen aslinya; kutipan dan sumber di langkah berikutnya mengacu pada potongan ini.
                    </p>
                </header>

                {chunks.length === 0 ? (
                    <p className="text-sm text-muted-foreground">
                        Belum ada teks hasil ekstraksi untuk dokumen ini.
                    </p>
                ) : (
                    <ol className="grid gap-3">
                        {chunks.map((chunk) => (
                            <li
                                key={chunk.id}
                                className="rounded-xl border border-border bg-card p-4"
                            >
                                <p className="mb-2 text-xs text-muted-foreground">
                                    Potongan {chunk.chunk_index + 1}
                                    {chunk.page !== null &&
                                        ` · Halaman ${chunk.page}`}
                                    {chunk.section && ` · ${chunk.section}`}
                                </p>
                                <p className="text-sm whitespace-pre-wrap">
                                    {chunk.content}
                                </p>
                            </li>
                        ))}
                    </ol>
                )}

                {total > chunks.length && (
                    <p className="text-sm text-muted-foreground">
                        Menampilkan {chunks.length} dari {total} potongan.
                    </p>
                )}
            </div>
        </>
    );
}

DocumentsShow.layout = {
    breadcrumbs: [{ title: 'Proyek', href: index() }],
};
