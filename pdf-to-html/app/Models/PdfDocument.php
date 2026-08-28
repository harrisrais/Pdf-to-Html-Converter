<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PdfDocument extends Model
{
    protected $fillable = [
        'gazette_id',
        'file_path',
        'file_size',
        'page_count',
        'status',
        'error_message',
        'processed_at',
    ];

    protected $casts = [
        'processed_at' => 'datetime',
    ];

    public function gazette(): BelongsTo
    {
        return $this->belongsTo(Gazette::class);
    }

    public function pages(): HasMany
    {
        return $this->hasMany(PdfPage::class);
    }
}