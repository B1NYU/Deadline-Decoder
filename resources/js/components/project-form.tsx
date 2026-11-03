import { Form, Link } from '@inertiajs/react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import type { Project } from '@/types/project';

type Props = {
    formAttributes: { action: string; method: 'post' };
    project?: Project;
    submitLabel: string;
    cancelHref: string;
};

export function ProjectForm({
    formAttributes,
    project,
    submitLabel,
    cancelHref,
}: Props) {
    return (
        <Form {...formAttributes} className="grid gap-6">
            {({ processing, errors }) => (
                <>
                    <div className="grid gap-2">
                        <Label htmlFor="title">Judul proyek</Label>
                        <Input
                            id="title"
                            name="title"
                            required
                            autoFocus
                            maxLength={150}
                            defaultValue={project?.title}
                            placeholder="Contoh: Tugas Besar Basis Data"
                            aria-invalid={Boolean(errors.title)}
                        />
                        <InputError message={errors.title} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="description">
                            Deskripsi{' '}
                            <span className="font-normal text-muted-foreground">
                                (opsional)
                            </span>
                        </Label>
                        <Textarea
                            id="description"
                            name="description"
                            maxLength={2000}
                            defaultValue={project?.description ?? ''}
                            placeholder="Mata kuliah, dosen, atau catatan singkat."
                            aria-invalid={Boolean(errors.description)}
                        />
                        <InputError message={errors.description} />
                    </div>

                    <div className="flex flex-wrap items-center gap-3">
                        <Button type="submit" disabled={processing}>
                            {processing && <Spinner />}
                            {submitLabel}
                        </Button>
                        <Button type="button" variant="outline" asChild>
                            <Link href={cancelHref}>Batal</Link>
                        </Button>
                    </div>
                </>
            )}
        </Form>
    );
}
