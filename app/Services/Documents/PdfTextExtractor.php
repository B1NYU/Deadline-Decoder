<?php

namespace App\Services\Documents;

use Smalot\PdfParser\Parser;
use Throwable;

class PdfTextExtractor
{
    public function __construct(private int $maxPages) {}

    /**
     * Extract the text of every page that has text. OCR is not supported.
     *
     * @return list<array{page: int|null, text: string}>
     *
     * @throws DocumentExtractionException
     */
    public function extract(string $path): array
    {
        try {
            $pages = array_values((new Parser)->parseFile($path)->getPages());
        } catch (Throwable $exception) {
            throw new DocumentExtractionException(
                'PDF tidak dapat dibaca. Berkasnya mungkin rusak atau dilindungi kata sandi.',
                previous: $exception,
            );
        }

        if (count($pages) > $this->maxPages) {
            throw new DocumentExtractionException("PDF melebihi batas {$this->maxPages} halaman.");
        }

        $segments = [];

        foreach ($pages as $index => $page) {
            try {
                $text = $page->getText();
            } catch (Throwable) {
                $text = '';
            }

            if (trim($text) !== '') {
                $segments[] = ['page' => $index + 1, 'text' => $text];
            }
        }

        if ($segments === []) {
            throw new DocumentExtractionException(
                'PDF ini tidak berisi teks yang dapat diekstrak (kemungkinan hasil pindaian). Dukungan OCR belum tersedia, jadi tempel teksnya secara manual.',
            );
        }

        return $segments;
    }
}
