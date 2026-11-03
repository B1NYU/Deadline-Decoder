import { Head, Link } from '@inertiajs/react';
import { CalendarClock, FileSearch, ListChecks } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { login, register } from '@/routes';

const steps = [
    {
        icon: FileSearch,
        title: 'Pahami instruksi',
        body: 'Tempel teks atau unggah PDF dari dosen. Persyaratan ditarik keluar beserta kutipan dan lokasi sumbernya.',
    },
    {
        icon: CalendarClock,
        title: 'Lihat tenggat yang benar-benar tertulis',
        body: 'Tanggal hanya muncul jika tercantum di dokumen. Jika tidak ada atau ambigu, aplikasi bilang begitu, bukan menebak.',
    },
    {
        icon: ListChecks,
        title: 'Rencanakan dan pantau',
        body: 'AI mengusulkan pecahan tugas dan urutannya. Kamu yang meninjau, mengubah, dan menyetujui sebelum tersimpan.',
    },
];

export default function Welcome() {
    return (
        <>
            <Head title="Deadline Decoder" />
            <div className="min-h-screen bg-background text-foreground">
                <header className="mx-auto flex w-full max-w-5xl items-center justify-between p-6">
                    <div className="flex items-center gap-2 font-semibold">
                        <div className="flex size-8 items-center justify-center rounded-md bg-primary text-primary-foreground">
                            <FileSearch className="size-5" />
                        </div>
                        Deadline Decoder
                    </div>
                    <nav className="flex items-center gap-2">
                        <Button variant="ghost" asChild>
                            <Link href={login()}>Masuk</Link>
                        </Button>
                        <Button asChild>
                            <Link href={register()}>Daftar</Link>
                        </Button>
                    </nav>
                </header>

                <main className="mx-auto w-full max-w-5xl px-6 pt-12 pb-20 md:pt-20">
                    <p className="mb-4 inline-block rounded-full border border-border bg-secondary px-3 py-1 text-xs font-medium text-secondary-foreground">
                        Versi pengembangan
                    </p>
                    <h1 className="max-w-2xl text-4xl font-semibold tracking-tight text-balance md:text-5xl">
                        Ubah instruksi tugas yang panjang menjadi rencana kerja yang jelas.
                    </h1>
                    <p className="mt-5 max-w-xl text-lg text-muted-foreground">
                        Dokumen dosen tetap jadi acuan utama. Deadline Decoder membantumu menemukan persyaratan dan tenggatnya, lalu merencanakan pengerjaannya.
                    </p>
                    <div className="mt-8 flex flex-wrap gap-3">
                        <Button size="lg" asChild>
                            <Link href={register()}>Mulai dengan akun baru</Link>
                        </Button>
                        <Button size="lg" variant="outline" asChild>
                            <Link href={login()}>Saya sudah punya akun</Link>
                        </Button>
                    </div>

                    <ul className="mt-16 grid gap-6 md:grid-cols-3">
                        {steps.map((step) => (
                            <li
                                key={step.title}
                                className="rounded-xl border border-border bg-card p-5"
                            >
                                <step.icon className="mb-3 size-6 text-primary" />
                                <h2 className="font-medium">{step.title}</h2>
                                <p className="mt-1 text-sm text-muted-foreground">
                                    {step.body}
                                </p>
                            </li>
                        ))}
                    </ul>
                </main>
            </div>
        </>
    );
}
