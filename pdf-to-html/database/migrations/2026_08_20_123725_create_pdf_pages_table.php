<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('pdf_pages', function (Blueprint $table) {
            $table->id();

            $table->foreignId('pdf_document_id')
                ->constrained('pdf_documents')
                ->cascadeOnDelete();

            $table->unsignedInteger('page_number');

            $table->longText('text')->nullable();

            $table->longText('html')->nullable();

            $table->string('status')->default('pending');

            $table->text('error_message')->nullable();

            $table->timestamp('processed_at')->nullable();

            $table->timestamps();

            $table->unique([
                'pdf_document_id',
                'page_number',
            ]);

            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pdf_pages');
    }
};