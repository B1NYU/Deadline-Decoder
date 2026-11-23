import { Form, Head, Link, usePoll } from '@inertiajs/react';
import { Pencil, Trash2 } from 'lucide-react';
import { useEffect } from 'react';
import { AddDocumentCard } from '@/components/add-document-card';
import { DocumentList } from '@/components/document-list';
import { ProjectStatusBadge } from '@/components/project-status-badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
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
import { destroy, edit, index } from '@/routes/projects';
import type { DocumentItem, DocumentLimits } from '@/types/document';
import type { Project } from '@/types/project';

type Props = {
    project: Project;
    documents: DocumentItem[];
    limits: DocumentLimits;
};

export default function ProjectsShow({ project, documents, limits }: Props) {
    const { start, stop } = usePoll(
        2000,
        { only: ['documents'] },
        { autoStart: false },
    );

    const hasActiveDocuments = documents.some(
        (document) =>
            document.status === 'pending' || document.status === 'processing',
    );

    useEffect(() => {
        if (hasActiveDocuments) {
            start();
        } else {
            stop();
        }

        return stop;
    }, [hasActiveDocuments]);

    return (
        <>
            <Head title={project.title} />
            <div className="mx-auto flex w-full max-w-5xl flex-col gap-8 p-4 md:p-8">
                <header className="flex flex-wrap items-start justify-between gap-4">
                    <div className="min-w-0">
                        <div className="mb-2 flex items-center gap-3">
                            <ProjectStatusBadge status={project.status} />
                            <span className="text-xs text-muted-foreground">
                                Dibuat {formatDate(project.created_at)}
                            </span>
                        </div>
                        <h1 className="text-2xl font-semibold tracking-tight text-balance">
                            {project.title}
                        </h1>
                    </div>

                    <div className="flex items-center gap-2">
                        <Button variant="outline" asChild>
                            <Link href={edit(project.id)}>
                                <Pencil /> Ubah
                            </Link>
                        </Button>

                        <Dialog>
                            <DialogTrigger asChild>
                                <Button variant="destructive">
                                    <Trash2 /> Hapus
                                </Button>
                            </DialogTrigger>
                            <DialogContent>
                                <DialogHeader>
                                    <DialogTitle>Hapus proyek ini?</DialogTitle>
                                    <DialogDescription>
                                        "{project.title}" akan dihapus permanen, termasuk semua dokumen dan data yang terkait dengannya. Tindakan ini tidak bisa dibatalkan.
                                    </DialogDescription>
                                </DialogHeader>
                                <Form {...destroy.form(project.id)}>
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
                </header>

                <Card>
                    <CardHeader>
                        <CardTitle className="text-base">Deskripsi</CardTitle>
                    </CardHeader>
                    <CardContent className="text-sm whitespace-pre-line">
                        {project.description ?? (
                            <span className="text-muted-foreground">
                                Belum ada deskripsi.
                            </span>
                        )}
                    </CardContent>
                </Card>

                <section className="grid gap-4">
                    <h2 className="text-lg font-medium">Dokumen instruksi</h2>
                    <AddDocumentCard projectId={project.id} limits={limits} />
                    <DocumentList projectId={project.id} documents={documents} />
                </section>

                <Card className="border-dashed">
                    <CardHeader>
                        <CardTitle className="text-base">
                            Persyaratan dan tugas
                        </CardTitle>
                        <CardDescription>
                            Belum tersedia di versi ini. Ekstraksi persyaratan dengan AI dan perencanaan tugas ditambahkan pada tahap pengembangan berikutnya.
                        </CardDescription>
                    </CardHeader>
                </Card>
            </div>
        </>
    );
}

ProjectsShow.layout = {
    breadcrumbs: [{ title: 'Proyek', href: index() }],
};
