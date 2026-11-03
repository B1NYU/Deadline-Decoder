import { Form, Head, Link } from '@inertiajs/react';
import { Pencil, Trash2 } from 'lucide-react';
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
import type { Project } from '@/types/project';

export default function ProjectsShow({ project }: { project: Project }) {
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
                                        "{project.title}" akan dihapus permanen, termasuk semua data yang terkait dengannya. Tindakan ini tidak bisa dibatalkan.
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

                <Card className="border-dashed">
                    <CardHeader>
                        <CardTitle className="text-base">
                            Instruksi, persyaratan, dan tugas
                        </CardTitle>
                        <CardDescription>
                            Belum tersedia di versi ini. Unggah dokumen, ekstraksi persyaratan, dan perencanaan tugas akan ditambahkan pada tahap pengembangan berikutnya.
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
