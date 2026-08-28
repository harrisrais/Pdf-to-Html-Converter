<?php

namespace App\Console\Commands;

use App\Models\PdfDocument;
use App\Services\PdfProcessor;
use Illuminate\Console\Command;

class ProcessPdf extends Command
{
    protected $signature = 'pdf:process
                            {--id= : Process a specific PDF document}
                            {--limit=10 : Maximum number of PDFs to process}';

    protected $description = 'Convert downloaded PDFs into page-level HTML';

    public function handle(PdfProcessor $processor): int
    {
        $query = PdfDocument::query()
            ->whereIn('status', ['pending', 'failed'])
            ->orderBy('id');

        if ($this->option('id')) {
            $query->where('id', $this->option('id'));
        } else {
            $query->limit((int) $this->option('limit'));
        }

        $documents = $query->get();

        if ($documents->isEmpty()) {
            $this->info('No PDFs waiting for processing.');

            return self::SUCCESS;
        }

        foreach ($documents as $document) {
            $this->info(
                "Processing PDF document #{$document->id}"
            );

            try {
                $processor->process($document);

                $this->info(
                    "PDF #{$document->id} completed."
                );
            } catch (\Throwable $e) {
                $this->error(
                    "PDF #{$document->id} failed: "
                    . $e->getMessage()
                );
            }
        }

        return self::SUCCESS;
    }
}