<?php

namespace App\Services;

use App\Models\PdfDocument;
use App\Models\PdfPage;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\File;
use RuntimeException;

class PdfProcessor
{
    public function process(PdfDocument $document): void
    {
        $document->update([
            'status' => 'processing',
            'error_message' => null,
        ]);

        try {
            $pdfPath = $document->file_path;

            if (!file_exists($pdfPath)) {
                throw new RuntimeException(
                    "PDF file does not exist: {$pdfPath}"
                );
            }

            /*
             * Get total page count using pdfinfo.
             */
            $pageCount = $this->getPageCount($pdfPath);

            $document->update([
                'page_count' => $pageCount,
            ]);

            /*
             * Process each page independently.
             */
            for ($pageNumber = 1; $pageNumber <= $pageCount; $pageNumber++) {
                $this->processPage(
                    $document,
                    $pdfPath,
                    $pageNumber
                );
            }

            /*
             * Only mark the document completed after
             * every page has successfully processed.
             */
            $failedPages = $document->pages()
                ->where('status', '!=', 'completed')
                ->count();

            if ($failedPages > 0) {
                throw new RuntimeException(
                    "{$failedPages} page(s) failed processing."
                );
            }

            $document->update([
                'status' => 'completed',
                'processed_at' => now(),
            ]);
        } catch (\Throwable $e) {
            $document->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    private function getPageCount(string $pdfPath): int
    {
        $result = Process::run([
            'pdfinfo',
            $pdfPath,
        ]);

        if ($result->failed()) {
            throw new RuntimeException(
                'pdfinfo failed: ' . $result->errorOutput()
            );
        }

        if (!preg_match('/^Pages:\s+(\d+)/mi', $result->output(), $matches)) {
            throw new RuntimeException(
                'Could not determine PDF page count.'
            );
        }

        return (int) $matches[1];
    }

    private function processPage(
        PdfDocument $document,
        string $pdfPath,
        int $pageNumber
    ): void {
        $page = PdfPage::firstOrCreate(
            [
                'pdf_document_id' => $document->id,
                'page_number' => $pageNumber,
            ],
            [
                'status' => 'pending',
            ]
        );

        /*
         * Already completed → don't process again.
         */
        if ($page->status === 'completed') {
            return;
        }

        $page->update([
            'status' => 'processing',
            'error_message' => null,
        ]);

        try {
            $text = $this->extractPageText(
                $pdfPath,
                $pageNumber
            );

            $html = $this->textToHtml($text);

            /*
             * Save generated HTML to local storage
             * so we can inspect the actual output.
             */
            $htmlDirectory = storage_path(
                'app/pdf-html/' . $document->id
            );

            File::ensureDirectoryExists($htmlDirectory);

            $htmlPath = $htmlDirectory . '/page-' . $pageNumber . '.html';

            File::put($htmlPath, $html);

            $page->update([
                'text' => $text,
                'html' => $html,
                'status' => 'completed',
                'processed_at' => now(),
            ]);

        } catch (\Throwable $e) {

            $page->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    private function extractPageText(
        string $pdfPath,
        int $pageNumber
    ): string {
        $result = Process::run([
            'pdftotext',
            '-f',
            (string) $pageNumber,
            '-l',
            (string) $pageNumber,
            '-layout',
            $pdfPath,
            '-',
        ]);

        if ($result->failed()) {
            throw new RuntimeException(
                "pdftotext failed on page {$pageNumber}: "
                . $result->errorOutput()
            );
        }

        return trim($result->output());
    }

    private function textToHtml(string $text): string
    {
        if ($text === '') {
            return '<div class="pdf-page"></div>';
        }

        /*
         * Preserve line structure while safely escaping
         * extracted PDF text.
         */
        $escaped = htmlspecialchars(
            $text,
            ENT_QUOTES | ENT_SUBSTITUTE,
            'UTF-8'
        );

        return '<div class="pdf-page"><pre>'
            . $escaped
            . '</pre></div>';
    }
}