import { Head } from '@inertiajs/react';
import { ProjectForm } from '@/components/project-form';
import { create, index, store } from '@/routes/projects';

export default function ProjectsCreate() {
    return (
        <>
            <Head title="Proyek baru" />
            <div className="mx-auto w-full max-w-2xl p-4 md:p-8">
                <h1 className="text-2xl font-semibold tracking-tight">
                    Proyek baru
                </h1>
                <p className="mt-1 mb-8 text-sm text-muted-foreground">
                    Beri nama proyek. Dokumen instruksinya ditambahkan setelah proyek dibuat.
                </p>
                <ProjectForm
                    formAttributes={store.form()}
                    submitLabel="Buat proyek"
                    cancelHref={index().url}
                />
            </div>
        </>
    );
}

ProjectsCreate.layout = {
    breadcrumbs: [
        { title: 'Proyek', href: index() },
        { title: 'Proyek baru', href: create() },
    ],
};
