<?php

declare(strict_types=1);

namespace App\Services;

use RuntimeException;

class StorageService
{
    private string $storagePath;

    private string $uploadsPath;

    public function __construct()
    {
        /*
         * Project root:
         *
         * /inventory-saas
         *
         * app/Services/StorageService.php
         *        ↑
         * dirname(__DIR__, 2)
         */
        $this->storagePath =
            dirname(__DIR__, 2) . '/storage';

        $this->uploadsPath =
            $this->storagePath . '/uploads';
    }

    /**
     * Get the main storage directory.
     */
    public function path(string $path = ''): string
    {
        return $this->buildPath(
            $this->storagePath,
            $path
        );
    }

    /**
     * Get the uploads directory.
     */
    public function uploadsPath(string $path = ''): string
    {
        return $this->buildPath(
            $this->uploadsPath,
            $path
        );
    }

    /**
     * Ensure a storage directory exists.
     */
    public function ensureDirectory(string $directory): string
    {
        $path = $this->uploadsPath($directory);

        if (is_dir($path)) {
            return $path;
        }

        if (
            !mkdir(
                $path,
                0755,
                true
            )
            && !is_dir($path)
        ) {
            throw new RuntimeException(
                'Unable to create storage directory.'
            );
        }

        return $path;
    }

    /**
     * Convert a relative storage path into
     * an absolute filesystem path.
     *
     * Example:
     *
     * uploads/businesses/logos/logo.png
     *
     * becomes:
     *
     * /project/storage/uploads/businesses/logos/logo.png
     */
    public function absolutePath(
        string $relativePath
    ): string {
        return $this->path(
            ltrim($relativePath, '/')
        );
    }

    /**
     * Check whether a stored file exists.
     */
    public function exists(
        ?string $relativePath
    ): bool {
        if (!$relativePath) {
            return false;
        }

        $path = $this->absolutePath(
            $relativePath
        );

        return is_file($path);
    }

    /**
     * Delete a stored file safely.
     */
    public function delete(
        ?string $relativePath
    ): bool {
        if (!$relativePath) {
            return false;
        }

        $storageRoot = realpath(
            $this->storagePath
        );

        if ($storageRoot === false) {
            return false;
        }

        $filePath = realpath(
            $this->absolutePath($relativePath)
        );

        /*
         * Prevent path traversal.
         */
        if (
            $filePath === false
            || !str_starts_with(
                $filePath,
                $storageRoot . DIRECTORY_SEPARATOR
            )
        ) {
            return false;
        }

        if (!is_file($filePath)) {
            return false;
        }

        return unlink($filePath);
    }

    /**
     * Get the storage root.
     */
    public function getStoragePath(): string
    {
        return $this->storagePath;
    }

    /**
     * Get the uploads root.
     */
    public function getUploadsPath(): string
    {
        return $this->uploadsPath;
    }

    /**
     * Build a safe filesystem path.
     */
    private function buildPath(
        string $base,
        string $path
    ): string {
        $path = trim($path, '/');

        if ($path === '') {
            return $base;
        }

        return $base . '/' . $path;
    }
}

