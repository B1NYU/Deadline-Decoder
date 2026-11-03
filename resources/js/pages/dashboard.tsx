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
import { dashboard } from '@/routes';
import { create, index, show } from '@/routes/projects';
import type { ProjectSummary } from '@/types/project';

type Props = {
    projectCount: number;
    recentProjects: ProjectSummary[];
};

export default function Dashboard({ projectCount, recentProjects }: Props) {
    return (
        <>
            <Head title="Dashboard" />
            <div className="mx-auto flex w-full max-w-5xl flex-col gap-8 p-4 md:p-8">
                <header>
                    <h1 className="text-2xl font-semibold tracking-tight">
                        Dashboard
                    </h1>
                    <p className="mt-1 text-sm text-muted-foreground">
                        Ringkasan proyek milikmu.
                    </p>
                </header>

                <Card>
                    <CardHeader>
                        <CardDescription>Total proyek</CardDescription>
                        <CardTitle className="text-4xl tabular-nums">
                            {projectCount}
                        </CardTitle>
                    </CardHeader>
                </Card>

                <section className="grid gap-4">
                    <div className="flex items-center justify-between">
                        <h2 className="text-lg font-medium">Proyek terbaru</h2>
                        {projectCount > 0 && (
                            <Button variant="ghost" size="sm" asChild>
                                <Link href={index()}>Lihat semua</Link>
                            </Button>
                        )}
                    </div>

                    {recentProjects.length === 0 ? (
                        <div className="flex flex-col items-center gap-3 rounded-xl border border-dashed border-border p-10 text-center">
                            <FolderOpen className="size-8 text-muted-foreground" />
                            <p className="font-medium">Belum ada proyek</p>
                            <p className="max-w-sm text-sm text-muted-foreground">
                                Buat proyek untuk setiap tugas atau proyek kuliah, lalu simpan instruksinya di sana.
                            </p>
                            <Button asChild>
                                <Link href={create()}>
                                    <Plus /> Buat proyek pertama
                                </Link>
                            </Button>
                        </div>
                    ) : (
                        <ul className="divide-y divide-border rounded-xl border border-border bg-card">
                            {recentProjects.map((project) => (
                                <li key={project.id}>
                                    <Link
                                        href={show(project.id)}
                                        className="flex items-center justify-between gap-4 p-4 outline-none hover:bg-accent focus-visible:bg-accent"
                                    >
                                        <div className="min-w-0">
                                            <p className="truncate font-medium">
                                                {project.title}
                                            </p>
                                            <p className="text-xs text-muted-foreground">
                                                Dibuat {formatDate(project.created_at)}
                                            </p>
                                        </div>
                                        <ProjectStatusBadge status={project.status} />
                                    </Link>
                                </li>
                            ))}
                        </ul>
                    )}
                </section>
            </div>
        </>
    );
}

Dashboard.layout = {
    breadcrumbs: [
        {
            title: 'Dashboard',
            href: dashboard(),
        },
    ],
};
