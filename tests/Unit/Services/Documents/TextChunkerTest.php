<?php

use App\Services\Documents\TextChunker;

test('it groups paragraphs without exceeding the maximum size', function () {
    $paragraph = trim(str_repeat('kata ', 30));
    $text = implode("\n\n", array_fill(0, 4, $paragraph));

    $chunks = (new TextChunker(maxChars: 320))->chunk([['page' => null, 'text' => $text]]);

    expect($chunks)->toHaveCount(2)
        ->and(array_column($chunks, 'chunk_index'))->toBe([0, 1])
        ->and(max(array_map(fn (array $chunk) => mb_strlen($chunk['content']), $chunks)))->toBeLessThanOrEqual(320);
});

test('it never crosses a page boundary and keeps the page numbers', function () {
    $chunks = (new TextChunker)->chunk([
        ['page' => 1, 'text' => 'Isi halaman satu.'],
        ['page' => 2, 'text' => 'Isi halaman dua.'],
    ]);

    expect(array_column($chunks, 'page'))->toBe([1, 2])
        ->and(array_column($chunks, 'chunk_index'))->toBe([0, 1]);
});

test('it carries the latest heading over to later chunks', function () {
    $chunks = (new TextChunker)->chunk([
        ['page' => 1, 'text' => "1. Ketentuan\nIsi satu."],
        ['page' => 2, 'text' => 'Lanjutan isi.'],
    ]);

    expect(array_column($chunks, 'section'))->toBe(['1. Ketentuan', '1. Ketentuan']);
});

test('it splits a long paragraph that has no blank lines', function () {
    $sentences = array_map(
        fn (int $number) => "Kalimat nomor {$number} berisi keterangan yang cukup panjang untuk uji pemisahan potongan teks.",
        range(1, 10),
    );

    $chunks = (new TextChunker(maxChars: 250))->chunk([['page' => null, 'text' => implode(' ', $sentences)]]);

    expect(count($chunks))->toBeGreaterThan(1)
        ->and($chunks[0]['content'])->toStartWith('Kalimat nomor 1')
        ->and(max(array_map(fn (array $chunk) => mb_strlen($chunk['content']), $chunks)))->toBeLessThanOrEqual(250);
});

test('it hard cuts a single unit that is longer than the limit', function () {
    $chunks = (new TextChunker(maxChars: 300))->chunk([['page' => null, 'text' => str_repeat('x', 1000)]]);

    expect($chunks)->toHaveCount(4)
        ->and(implode('', array_column($chunks, 'content')))->toBe(str_repeat('x', 1000));
});

test('it returns no chunks for empty text', function (string $text) {
    expect((new TextChunker)->chunk([['page' => null, 'text' => $text]]))->toBe([]);
})->with([
    'empty' => '',
    'only whitespace' => "  \n\n \t ",
]);

test('it removes null bytes and invalid utf-8 sequences', function () {
    $chunks = (new TextChunker)->chunk([['page' => null, 'text' => "Halo\0 dunia \xC3\x28"]]);

    expect($chunks[0]['content'])->not->toContain("\0")
        ->and(mb_check_encoding($chunks[0]['content'], 'UTF-8'))->toBeTrue();
});
