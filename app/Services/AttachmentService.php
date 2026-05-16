<?php

namespace App\Services;

use App\Models\Attachment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AttachmentService
{
    /**
     * Allowed file extensions.
     */
    public const ALLOWED_EXTENSIONS = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'jpeg', 'png'];

    /**
     * Max file size in bytes (10MB).
     */
    public const MAX_FILE_SIZE = 10485760;

    /**
     * Blocked executable extensions.
     */
    public const BLOCKED_EXTENSIONS = ['exe', 'sh', 'bat', 'cmd', 'msi', 'ps1', 'vbs', 'js', 'php'];

    /**
     * Get Filament accepted file types (MIME types).
     */
    public static function getAcceptedFileTypes(): array
    {
        return [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'image/jpeg',
            'image/png',
        ];
    }

    /**
     * Upload a file and create an Attachment record.
     *
     * @param UploadedFile $file
     * @param Model $attachable The parent model (Product, StockTransaction, etc.)
     * @param int|null $userId
     * @return Attachment
     * @throws \Exception
     */
    public function upload(UploadedFile $file, Model $attachable, ?int $userId = null): Attachment
    {
        $this->validate($file);

        $extension = strtolower($file->getClientOriginalExtension());
        $originalName = $file->getClientOriginalName();
        $sanitizedName = $this->sanitizeFileName($originalName);
        $directory = 'attachments/' . strtolower(class_basename($attachable)) . '/' . $attachable->id;
        $path = $file->storeAs($directory, $sanitizedName, 'public');

        return Attachment::create([
            'attachable_id' => $attachable->id,
            'attachable_type' => get_class($attachable),
            'file_path' => $path,
            'file_name' => $originalName,
            'file_type' => $extension,
            'file_size' => $file->getSize(),
            'uploaded_by' => $userId,
        ]);
    }

    /**
     * Upload multiple files.
     *
     * @param array $files Array of UploadedFile
     * @param Model $attachable
     * @param int|null $userId
     * @return array
     */
    public function uploadMultiple(array $files, Model $attachable, ?int $userId = null): array
    {
        $attachments = [];
        foreach ($files as $file) {
            if ($file instanceof UploadedFile) {
                $attachments[] = $this->upload($file, $attachable, $userId);
            }
        }
        return $attachments;
    }

    /**
     * Delete an attachment and its file.
     *
     * @param Attachment $attachment
     * @return void
     */
    public function delete(Attachment $attachment): void
    {
        if (Storage::disk('public')->exists($attachment->file_path)) {
            Storage::disk('public')->delete($attachment->file_path);
        }
        $attachment->delete();
    }

    /**
     * Validate file before upload.
     *
     * @param UploadedFile $file
     * @throws \Exception
     */
    private function validate(UploadedFile $file): void
    {
        $extension = strtolower($file->getClientOriginalExtension());

        if (in_array($extension, self::BLOCKED_EXTENSIONS)) {
            throw new \Exception("File executable tidak diperbolehkan: .{$extension}");
        }

        if (!in_array($extension, self::ALLOWED_EXTENSIONS)) {
            throw new \Exception("Tipe file tidak didukung: .{$extension}. Tipe yang didukung: " . implode(', ', self::ALLOWED_EXTENSIONS));
        }

        if ($file->getSize() > self::MAX_FILE_SIZE) {
            throw new \Exception("Ukuran file melebihi batas maksimum 10MB.");
        }
    }

    /**
     * Sanitize file name to prevent directory traversal and special character issues.
     *
     * @param string $fileName
     * @return string
     */
    private function sanitizeFileName(string $fileName): string
    {
        $extension = pathinfo($fileName, PATHINFO_EXTENSION);
        $name = pathinfo($fileName, PATHINFO_FILENAME);

        // Remove special characters, keep alphanumeric, dash, underscore
        $name = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $name);
        $name = preg_replace('/_+/', '_', $name);
        $name = trim($name, '_');

        // Add timestamp to prevent name conflicts
        return $name . '_' . time() . '.' . strtolower($extension);
    }
}
