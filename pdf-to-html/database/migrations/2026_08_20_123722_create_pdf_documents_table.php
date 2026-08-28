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
        Schema::create('pdf_documents', function (Blueprint $table) {
            $table->id();

            $table->foreignId('gazette_id')
                ->constrained('gazettes')
                ->cascadeOnDelete();

            $table->string('file_path');
            $table->unsignedBigInteger('file_size')->nullable();

            $table->unsignedInteger('page_count')->nullable();

            $table->string('status')->default('pending');

            $table->text('error_message')->nullable();

            $table->timestamp('processed_at')->nullable();

            $table->timestamps();

            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pdf_documents');
    }
};