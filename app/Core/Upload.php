<?php
/**
 * NexUse — image upload handling.
 *
 * MVC layer: Core service. Validates and stores uploaded images.
 *
 * The `fileinfo` extension is not installed on the development machine, so the
 * image type is established with getimagesize(), which is part of core PHP,
 * alongside an extension allowlist and a size cap.
 */

declare(strict_types=1);

namespace App\Core;

class Upload
{
    public const MAX_BYTES = 2 * 1024 * 1024; // 2 MB

    private const ALLOWED = [
        IMAGETYPE_JPEG => 'jpg',
        IMAGETYPE_PNG  => 'png',
        IMAGETYPE_GIF  => 'gif',
        IMAGETYPE_WEBP => 'webp',
    ];

    /**
     * Validate and store one uploaded image.
     *
     * @param  array{name: string, type: string, tmp_name: string, error: int, size: int} $file
     * @return array{ok: bool, path?: string, error?: string}
     */
    public static function image(array $file): array
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return ['ok' => false, 'error' => 'No file was selected.'];
        }

        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['ok' => false, 'error' => 'The file could not be uploaded. Try a smaller image.'];
        }

        if ($file['size'] > self::MAX_BYTES) {
            return ['ok' => false, 'error' => 'Images must be 2 MB or smaller.'];
        }

        if (!is_uploaded_file($file['tmp_name'])) {
            return ['ok' => false, 'error' => 'The upload could not be verified.'];
        }

        // getimagesize() returns false for anything that is not a real image.
        $info = @getimagesize($file['tmp_name']);

        if ($info === false) {
            return ['ok' => false, 'error' => 'That file is not a readable image.'];
        }

        if (!isset(self::ALLOWED[$info[2]])) {
            return ['ok' => false, 'error' => 'Images must be JPG, PNG, GIF or WEBP.'];
        }

        $directory = BASE_PATH . '/public/assets/uploads';

        if (!is_dir($directory) && !mkdir($directory, 0777, true) && !is_dir($directory)) {
            return ['ok' => false, 'error' => 'The upload folder could not be created.'];
        }

        $filename = bin2hex(random_bytes(16)) . '.' . self::ALLOWED[$info[2]];

        if (!move_uploaded_file($file['tmp_name'], $directory . '/' . $filename)) {
            return ['ok' => false, 'error' => 'The image could not be saved.'];
        }

        // Always store a relative path so the database stays portable.
        return ['ok' => true, 'path' => 'assets/uploads/' . $filename];
    }

    /**
     * Delete a stored upload, ignoring anything pointing outside the uploads folder.
     */
    public static function delete(?string $relativePath): void
    {
        if (empty($relativePath) || !str_starts_with($relativePath, 'assets/uploads/')) {
            return;
        }

        $full = BASE_PATH . '/public/' . $relativePath;

        if (is_file($full)) {
            @unlink($full);
        }
    }

    /**
     * Delete many stored uploads.
     *
     * @param list<string> $paths
     */
    public static function deleteMany(array $paths): void
    {
        foreach ($paths as $path) {
            self::delete($path);
        }
    }
}
