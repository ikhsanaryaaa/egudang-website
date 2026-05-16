<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attachment extends Model
{
    use HasFactory;

    protected $fillable = [
        'attachable_id',
        'attachable_type',
        'file_path',
        'file_name',
        'file_type',
        'file_size',
        'uploaded_by',
    ];

    /**
     * Get the parent attachable model (Product, StockTransaction, etc.).
     */
    public function attachable()
    {
        return $this->morphTo();
    }

    /**
     * Get the user who uploaded the file.
     */
    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * Check if the attachment is an image.
     */
    public function isImage(): bool
    {
        return in_array(strtolower($this->file_type), ['jpg', 'jpeg', 'png']);
    }

    /**
     * Check if the attachment is a document.
     */
    public function isDocument(): bool
    {
        return in_array(strtolower($this->file_type), ['pdf', 'doc', 'docx', 'xls', 'xlsx']);
    }

    /**
     * Get human-readable file size.
     */
    public function getFormattedSizeAttribute(): string
    {
        $bytes = $this->file_size;
        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 2) . ' MB';
        }
        if ($bytes >= 1024) {
            return round($bytes / 1024, 2) . ' KB';
        }
        return $bytes . ' B';
    }
}
