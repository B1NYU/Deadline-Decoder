import { Head, Link } from '@inertiajs/react';
import { FolderOpen, Plus } from 'lucide-react';
import { ProjectStatusBadge } from '@/components/project-status-badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { formatDate } from '@/lib/format-date';
import { create, index, show } from '@/routes/projects';
import type { Paginated, Project } from '@/types/project';

type Props = {
    projects: Paginated<Project>;
};

export default function ProjectsIndex({ projects }: Props) {
    return (
        <>
            <Head title="Proyek" />
            <div className="mx-auto flex w-full max-w-5xl flex-col gap-8 p-4 md:p-8">
                <header className="flex flex-wrap items-end justify-between gap-4">
                    <div>
                        <h1 className="text-2xl font-semibold tracking-tight">
                            Proyek
                        </h1>
                        <p className="mt-1 text-sm text-muted-foreground">
                            Satu proyek untuk satu tugas atau proyek kuliah.
                        </p>
                    </div>
                    <Button asChild>
                        <Link href={create()}>
                            <Plus /> Proyek baru
                        </Link>
                    </Button>
                </header>

                {projects.data.length === 0 ? (
                    <div className="flex flex-col items-center gap-3 rounded-xl border border-dashed border-border p-12 text-center">
                        <FolderOpen className="size-8 text-muted-foreground" />
                        <p className="font-medium">Belum ada proyek</p>
                        <p className="max-w-sm text-sm text-muted-foreground">
                            Mulai dengan membuat proyek, misalnya "Tugas Besar Basis Data". Instruksi dosen akan disimpan di dalamnya.
                        </p>
                        <Button asChild>
                            <Link href={create()}>
                                <Plus /> Buat proyek
                            </Link>
                        </Button>
                    </div>
                ) : (
                    <>
                        <ul className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                            {projects.data.map((project) => (
                                <li key={project.id}>
                                    <Link
                                        href={show(project.id)}
                                        prefetch
                                        className="group block rounded-xl outline-none focus-visible:ring-[3px] focus-visible:ring-ring/50"
                                    >
                                        <Card className="h-full transition-colors group-hover:border-primary/40">
                                            <CardHeader>
                                                <div className="flex items-start justify-between gap-3">
                                                    <CardTitle className="line-clamp-2 text-base">
                                                        {project.title}
                                                    </CardTitle>
                                                    <ProjectStatusBadge
                                                        status={project.status}
                                                    />
                                                </div>
                                                <CardDescription className="line-clamp-3">
                                                    {project.description ??
                                                        'Belum ada deskripsi.'}
                                                </CardDescription>
                                            </CardHeader>
                                            <CardContent className="text-xs text-muted-foreground">
                                                Dibuat {formatDate(project.created_at)}
                                            </CardContent>
                                        </Card>
                                    </Link>
                                </li>
                            ))}
                        </ul>

                        {projects.last_page > 1 && (
                            <nav
                                aria-label="Halaman proyek"
                                className="flex items-center justify-between"
                            >
                                {projects.prev_page_url ? (
                                    <Button variant="outline" asChild>
                                        <Link
                                            href={projects.prev_page_url}
                                            preserveScroll
                                        >
                                            Sebelumnya
                                        </Link>
                                    </Button>
                                ) : (
                                    <span />
                                )}
                                <span className="text-sm text-muted-foreground">
                                    Halaman {projects.current_page} dari{' '}
                                    {projects.last_page}
                                </span>
                                {projects.next_page_url ? (
                                    <Button variant="outline" asChild>
                                        <Link
                                            href={projects.next_page_url}
                                            preserveScroll
                                        >
                                            Berikutnya
                                        </Link>
                                    </Button>
                                ) : (
                                    <span />
                                )}
                            </nav>
                        )}
                    </>
                )}
            </div>
        </>
    );
}

ProjectsIndex.layout = {
    breadcrumbs: [
        {
            title: 'Proyek',
            href: index(),
        },
    ],
};
