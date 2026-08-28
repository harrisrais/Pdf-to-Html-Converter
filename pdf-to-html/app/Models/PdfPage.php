<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PdfPage extends Model
{
    protected $fillable = [
        'pdf_document_id',
        'page_number',
        'text',
        'html',
        'status',
        'error_message',
        'processed_at',
    ];

    protected $casts = [
        'processed_at' => 'datetime',
    ];

    public function pdfDocument(): BelongsTo
    {
        return $this->belongsTo(PdfDocument::class);
    }
}