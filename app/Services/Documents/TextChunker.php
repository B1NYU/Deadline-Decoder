<?php

namespace App\Services\Documents;

final class TextChunker
{
    public function __construct(private int $maxChars = 1200) {}

    /**
     * Split text into chunks that never cross a page boundary and never exceed the size limit.
     *
     * @param  list<array{page: int|null, text: string}>  $segments
     * @return list<array{chunk_index: int, page: int|null, section: string|null, content: string}>
     */
    public function chunk(array $segments): array
    {
        $chunks = [];
        $section = null;

        foreach ($segments as $segment) {
            $buffer = '';
            $bufferSection = $section;

            foreach ($this->paragraphs($segment['text']) as $paragraph) {
                $heading = $this->headingOf($paragraph);

                if ($heading !== null) {
                    $section = $heading;
                }

                foreach ($this->fit($paragraph) as $piece) {
                    if ($buffer !== '' && mb_strlen($buffer) + 2 + mb_strlen($piece) > $this->maxChars) {
                        $chunks[] = $this->makeChunk(count($chunks), $segment['page'], $bufferSection, $buffer);
                        $buffer = '';
                    }

                    if ($buffer === '') {
                        $bufferSection = $section;
                    }

                    $buffer = $buffer === '' ? $piece : $buffer."\n\n".$piece;
                }
            }

            if ($buffer !== '') {
                $chunks[] = $this->makeChunk(count($chunks), $segment['page'], $bufferSection, $buffer);
            }
        }

        return $chunks;
    }

    /**
     * @return array{chunk_index: int, page: int|null, section: string|null, content: string}
     */
    private function makeChunk(int $index, ?int $page, ?string $section, string $content): array
    {
        return ['chunk_index' => $index, 'page' => $page, 'section' => $section, 'content' => $content];
    }

    /**
     * @return list<string>
     */
    private function paragraphs(string $text): array
    {
        $text = str_replace("\0", '', mb_scrub($text));
        $text = (string) preg_replace("/\r\n?/", "\n", $text);
        $parts = preg_split("/\n{2,}/", trim($text)) ?: [];
        $paragraphs = [];

        foreach ($parts as $part) {
            $part = trim((string) preg_replace('/[ \t]+/', ' ', $part));

            if ($part !== '') {
                $paragraphs[] = $part;
            }
        }

        return $paragraphs;
    }

    /**
     * Split an oversized paragraph on sentence or line boundaries, hard-cutting only as a last resort.
     *
     * @return list<string>
     */
    private function fit(string $paragraph): array
    {
        if (mb_strlen($paragraph) <= $this->maxChars) {
            return [$paragraph];
        }

        $units = preg_split('/(?<=[.!?])\s+|\n+/u', $paragraph) ?: [$paragraph];
        $pieces = [];
        $current = '';

        foreach ($units as $unit) {
            $unit = trim($unit);

            if ($unit === '') {
                continue;
            }

            foreach (mb_str_split($unit, $this->maxChars) as $part) {
                if ($current !== '' && mb_strlen($current) + 1 + mb_strlen($part) > $this->maxChars) {
                    $pieces[] = $current;
                    $current = '';
                }

                $current = $current === '' ? $part : $current.' '.$part;
            }
        }

        if ($current !== '') {
            $pieces[] = $current;
        }

        return $pieces;
    }

    /**
     * A heuristic: short numbered lines ("1. Ketentuan") or short upper-case lines.
     * The result is only a hint for the reader, never treated as a verified fact.
     */
    private function headingOf(string $paragraph): ?string
    {
        $line = trim(explode("\n", $paragraph, 2)[0]);

        if ($line === '' || mb_strlen($line) > 80 || str_ends_with($line, '.')) {
            return null;
        }

        $isNumbered = preg_match('/^\d+(?:\.\d+)*[.)]\s+\S/u', $line) === 1;
        $isUpperCase = preg_match('/^[A-Z0-9][A-Z0-9 ,&\/()-]{3,78}$/u', $line) === 1;

        return $isNumbered || $isUpperCase ? $line : null;
    }
}
