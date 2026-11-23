import { Form } from '@inertiajs/react';
import { useState } from 'react';
import { store } from '@/actions/App/Http/Controllers/DocumentController';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import type { DocumentLimits } from '@/types/document';

type Props = {
    projectId: number;
    limits: DocumentLimits;
};

export function AddDocumentCard({ projectId, limits }: Props) {
    const [mode, setMode] = useState<'text' | 'pdf'>('text');

    return (
        <Card>
            <CardHeader>
                <CardTitle className="text-base">Tambah instruksi</CardTitle>
                <CardDescription>
                    Tempel teks instruksi atau unggah PDF yang teksnya bisa disalin. PDF hasil pindaian belum didukung karena belum ada OCR.
                </CardDescription>
            </CardHeader>
            <CardContent className="grid gap-4">
                <div
                    role="group"
                    aria-label="Jenis masukan"
                    className="inline-flex w-fit gap-1 rounded-md border border-border p-1"
                >
                    <Button
                        type="button"
                        size="sm"
                        variant={mode === 'text' ? 'default' : 'ghost'}
                        aria-pressed={mode === 'text'}
                        onClick={() => setMode('text')}
                    >
                        Tempel teks
                    </Button>
                    <Button
                        type="button"
                        size="sm"
                        variant={mode === 'pdf' ? 'default' : 'ghost'}
                        aria-pressed={mode === 'pdf'}
                        onClick={() => setMode('pdf')}
                    >
                        Unggah PDF
                    </Button>
                </div>

                <Form
                    key={mode}
                    {...store.form(projectId)}
                    resetOnSuccess
                    className="grid gap-4"
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="document-title">
                                    Judul{' '}
                                    <span className="font-normal text-muted-foreground">
                                        (opsional)
                                    </span>
                                </Label>
                                <Input
                                    id="document-title"
                                    name="title"
                                    maxLength={150}
                                    placeholder="Contoh: Instruksi Tugas Besar"
                                    aria-invalid={Boolean(errors.title)}
                                />
                                <InputError message={errors.title} />
                            </div>

                            {mode === 'text' ? (
                                <div className="grid gap-2">
                                    <Label htmlFor="document-text">
                                        Teks instruksi
                                    </Label>
                                    <Textarea
                                        id="document-text"
                                        name="text"
                                        required
                                        minLength={limits.minTextChars}
                                        maxLength={limits.maxTextChars}
                                        className="min-h-40"
                                        placeholder="Tempel instruksi dari dosen di sini."
                                        aria-invalid={Boolean(errors.text)}
                                    />
                                    <p className="text-xs text-muted-foreground">
                                        Maksimal {limits.maxTextChars.toLocaleString('id-ID')} karakter.
                                    </p>
                                    <InputError message={errors.text} />
                                </div>
                            ) : (
                                <div className="grid gap-2">
                                    <Label htmlFor="document-file">
                                        Berkas PDF
                                    </Label>
                                    <Input
                                        id="document-file"
                                        type="file"
                                        name="file"
                                        required
                                        accept="application/pdf,.pdf"
                                        aria-invalid={Boolean(errors.file)}
                                    />
                                    <p className="text-xs text-muted-foreground">
                                        Maksimal {limits.maxPdfMb} MB.
                                    </p>
                                    <InputError message={errors.file} />
                                </div>
                            )}

                            <div>
                                <Button type="submit" disabled={processing}>
                                    {processing && <Spinner />}
                                    Tambahkan
                                </Button>
                            </div>
                        </>
                    )}
                </Form>
            </CardContent>
        </Card>
    );
}
