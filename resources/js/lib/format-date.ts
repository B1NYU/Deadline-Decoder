export function formatDate(iso: string | null): string {
    if (!iso) {
        return '-';
    }

    return new Intl.DateTimeFormat('id-ID', { dateStyle: 'medium' }).format(
        new Date(iso),
    );
}
