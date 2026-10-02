<?php

declare(strict_types=1);

namespace App\Services;

use Psr\Http\Message\UploadedFileInterface;
use RuntimeException;

class FileUploadService
{
    private const DEFAULT_MAX_SIZE = 5 * 1024 * 1024; // 5 MB

    public function __construct(
        private StorageService $storageService
    ) {
    }

    /**
     * Upload an image.
     *
     * Example:
     *
     * uploadImage(
     *     $logo,
     *     'businesses/logos'
     * );
     *
     * Returns:
     *
     * [
     *     'path' => 'uploads/businesses/logos/abc123.png',
     *     'filename' => 'abc123.png',
     *     'original_name' => 'logo.png',
     *     'mime_type' => 'image/png',
     *     'size' => 12345
     * ]
     */
    public function uploadImage(
        UploadedFileInterface $file,
        string $directory,
        int $maxSize = self::DEFAULT_MAX_SIZE
    ): array {

        if ($file->getError() !== UPLOAD_ERR_OK) {
            throw new RuntimeException(
                $this->uploadErrorMessage(
                    $file->getError()
                )
            );
        }

        $size = $file->getSize();

        if ($size === null || $size <= 0) {
            throw new RuntimeException(
                'The uploaded file is empty.'
            );
        }

        if ($size > $maxSize) {
            throw new RuntimeException(
                'The uploaded file exceeds the maximum allowed size of 5 MB.'
            );
        }

        /*
         * Read uploaded file.
         */
        $stream = $file->getStream();

        $contents = $stream->getContents();

        if ($contents === '') {
            throw new RuntimeException(
                'Unable to read the uploaded file.'
            );
        }

        /*
         * Detect actual MIME type.
         *
         * Never trust the browser supplied MIME type.
         */
        $finfo = new \finfo(
            FILEINFO_MIME_TYPE
        );

        $mimeType =
            $finfo->buffer($contents);

        $allowedTypes = [
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/webp' => 'webp',
        ];

        if (!isset($allowedTypes[$mimeType])) {
            throw new RuntimeException(
                'Invalid image format. Please upload JPG, PNG or WEBP.'
            );
        }

        /*
         * Verify that the file is really an image.
         */
        if (
            @getimagesizefromstring(
                $contents
            ) === false
        ) {
            throw new RuntimeException(
                'The uploaded file is not a valid image.'
            );
        }

        /*
         * Generate random filename.
         */
        $filename =
            bin2hex(
                random_bytes(16)
            )
            . '.'
            . $allowedTypes[$mimeType];

        /*
         * Create directory.
         */
        $this->storageService
            ->ensureDirectory($directory);

        /*
         * Absolute target path.
         */
        $targetPath =
            $this->storageService
                ->uploadsPath(
                    trim($directory, '/')
                    . '/'
                    . $filename
                );

        /*
         * Move uploaded file.
         */
        $file->moveTo(
            $targetPath
        );

        if (!is_file($targetPath)) {
            throw new RuntimeException(
                'Unable to store the uploaded file.'
            );
        }

        /*
         * Relative path stored in DB.
         */
        $relativePath =
            'uploads/'
            . trim($directory, '/')
            . '/'
            . $filename;

        return [
            'path' =>
                $relativePath,

            'filename' =>
                $filename,

            'original_name' =>
                $file->getClientFilename()
                ?? $filename,

            'mime_type' =>
                $mimeType,

            'size' =>
                $size,
        ];
    }

    /**
     * Delete an uploaded file.
     */
    public function delete(
        ?string $relativePath
    ): bool {
        return $this->storageService
            ->delete($relativePath);
    }

    /**
     * Check whether an uploaded file exists.
     */
    public function exists(
        ?string $relativePath
    ): bool {
        return $this->storageService
            ->exists($relativePath);
    }

    /**
     * Get absolute filesystem path.
     */
    public function absolutePath(
        string $relativePath
    ): string {
        return $this->storageService
            ->absolutePath($relativePath);
    }

    private function uploadErrorMessage(
        int $error
    ): string {
        return match ($error) {

            UPLOAD_ERR_INI_SIZE,
            UPLOAD_ERR_FORM_SIZE =>
                'The uploaded file is too large.',

            UPLOAD_ERR_PARTIAL =>
                'The file upload was incomplete.',

            UPLOAD_ERR_NO_FILE =>
                'No file was uploaded.',

            UPLOAD_ERR_NO_TMP_DIR =>
                'The server temporary upload directory is missing.',

            UPLOAD_ERR_CANT_WRITE =>
                'The server could not write the uploaded file.',

            UPLOAD_ERR_EXTENSION =>
                'The upload was blocked by a server extension.',

            default =>
                'The file upload failed.',
        };
    }
}

