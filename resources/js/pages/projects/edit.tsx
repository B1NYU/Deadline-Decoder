import { Head } from '@inertiajs/react';
import { ProjectForm } from '@/components/project-form';
import { index, show, update } from '@/routes/projects';
import type { Project } from '@/types/project';

export default function ProjectsEdit({ project }: { project: Project }) {
    return (
        <>
            <Head title={`Ubah ${project.title}`} />
            <div className="mx-auto w-full max-w-2xl p-4 md:p-8">
                <h1 className="text-2xl font-semibold tracking-tight">
                    Ubah proyek
                </h1>
                <p className="mt-1 mb-8 text-sm text-muted-foreground">
                    Perubahan hanya memengaruhi nama dan deskripsi proyek.
                </p>
                <ProjectForm
                    formAttributes={update.form(project.id)}
                    project={project}
                    submitLabel="Simpan perubahan"
                    cancelHref={show(project.id).url}
                />
            </div>
        </>
    );
}

ProjectsEdit.layout = {
    breadcrumbs: [{ title: 'Proyek', href: index() }],
};
