<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Gazette extends Model
{
    protected $fillable = [
        'edition_date',
        'pdf_url',
        'file_path',
        'status',
        'file_size',
    ];

    protected $casts = [
        'edition_date' => 'date',
    ];

    public function pdfDocument(): HasOne
    {
        return $this->hasOne(PdfDocument::class);
    }
}