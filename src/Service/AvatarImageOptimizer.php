<?php

declare(strict_types=1);

namespace App\Service;

class AvatarImageOptimizer
{
    private const MAX_DIMENSION = 512;
    private const JPEG_QUALITY = 82;
    private const WEBP_QUALITY = 82;
    private const AVIF_QUALITY = 60;
    private const PNG_COMPRESSION = 8;

    public function optimize(string $absolutePath, string $mimeType): void
    {
        if (!is_file($absolutePath) || !function_exists('getimagesize') || !$this->isGdAvailable()) {
            return;
        }

        if ('image/gif' === $mimeType) {
            return;
        }

        $imageInfo = @getimagesize($absolutePath);
        if (!is_array($imageInfo)) {
            return;
        }

        [$width, $height] = $imageInfo;
        if (!is_int($width) || !is_int($height) || $width < 1 || $height < 1) {
            return;
        }

        $sourceImage = $this->createSourceImage($absolutePath, $mimeType);
        if (!$sourceImage instanceof \GdImage) {
            return;
        }

        $scale = min(1, self::MAX_DIMENSION / max($width, $height));
        $targetWidth = max(1, (int) round($width * $scale));
        $targetHeight = max(1, (int) round($height * $scale));

        $targetImage = imagecreatetruecolor($targetWidth, $targetHeight);
        if (!$targetImage instanceof \GdImage) {
            imagedestroy($sourceImage);

            return;
        }

        $this->preserveTransparency($targetImage, $mimeType);

        imagecopyresampled($targetImage, $sourceImage, 0, 0, 0, 0, $targetWidth, $targetHeight, $width, $height);

        $temporaryPath = $absolutePath.'.tmp';
        $saved = $this->saveOptimizedImage($targetImage, $temporaryPath, $mimeType);

        imagedestroy($sourceImage);
        imagedestroy($targetImage);

        if (!$saved) {
            @unlink($temporaryPath);

            return;
        }

        $this->replaceOptimizedFile($temporaryPath, $absolutePath);
    }

    private function isGdAvailable(): bool
    {
        return extension_loaded('gd')
            && function_exists('imagecreatetruecolor')
            && function_exists('imagecopyresampled')
            && function_exists('imagealphablending')
            && function_exists('imagesavealpha')
            && function_exists('imagefilledrectangle')
            && function_exists('imagecolorallocatealpha');
    }

    private function createSourceImage(string $absolutePath, string $mimeType): ?\GdImage
    {
        return match ($mimeType) {
            'image/jpeg' => function_exists('imagecreatefromjpeg') ? @imagecreatefromjpeg($absolutePath) ?: null : null,
            'image/png' => function_exists('imagecreatefrompng') ? @imagecreatefrompng($absolutePath) ?: null : null,
            'image/webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($absolutePath) ?: null : null,
            'image/avif' => function_exists('imagecreatefromavif') ? @imagecreatefromavif($absolutePath) ?: null : null,
            default => null,
        };
    }

    private function preserveTransparency(\GdImage $image, string $mimeType): void
    {
        if (\in_array($mimeType, ['image/png', 'image/webp', 'image/avif'], true)) {
            imagealphablending($image, false);
            imagesavealpha($image, true);
            $transparent = imagecolorallocatealpha($image, 0, 0, 0, 127);
            imagefilledrectangle($image, 0, 0, imagesx($image), imagesy($image), $transparent);
        }
    }

    private function saveOptimizedImage(\GdImage $image, string $absolutePath, string $mimeType): bool
    {
        return match ($mimeType) {
            'image/jpeg' => function_exists('imagejpeg') && @imagejpeg($image, $absolutePath, self::JPEG_QUALITY),
            'image/png' => function_exists('imagepng') && @imagepng($image, $absolutePath, self::PNG_COMPRESSION),
            'image/webp' => function_exists('imagewebp') && @imagewebp($image, $absolutePath, self::WEBP_QUALITY),
            'image/avif' => function_exists('imageavif') && @imageavif($image, $absolutePath, self::AVIF_QUALITY),
            default => false,
        };
    }

    private function replaceOptimizedFile(string $temporaryPath, string $absolutePath): void
    {
        if ($this->moveFile($temporaryPath, $absolutePath)) {
            return;
        }

        $backupPath = null;
        if (is_file($absolutePath)) {
            $backupPath = $this->buildBackupPath($absolutePath);
            if (!$this->moveFile($absolutePath, $backupPath)) {
                $this->deleteFile($temporaryPath);

                return;
            }
        }

        $replacementWritten = $this->moveFile($temporaryPath, $absolutePath);
        if (!$replacementWritten) {
            $replacementWritten = $this->copyFile($temporaryPath, $absolutePath);

            if ($replacementWritten) {
                $this->deleteFile($temporaryPath);
            }
        }

        if ($replacementWritten) {
            if (is_string($backupPath)) {
                $this->deleteFile($backupPath);
            }

            return;
        }

        if (is_string($backupPath) && is_file($backupPath) && !is_file($absolutePath)) {
            if (!$this->moveFile($backupPath, $absolutePath) && !$this->copyFile($backupPath, $absolutePath)) {
                $this->deleteFile($temporaryPath);

                return;
            }
        }

        if (is_string($backupPath)) {
            $this->deleteFile($backupPath);
        }

        $this->deleteFile($temporaryPath);
    }

    protected function moveFile(string $from, string $to): bool
    {
        return @rename($from, $to);
    }

    protected function copyFile(string $from, string $to): bool
    {
        return @copy($from, $to);
    }

    protected function deleteFile(string $path): bool
    {
        return @unlink($path);
    }

    protected function buildBackupPath(string $absolutePath): string
    {
        return sprintf('%s.backup-%s', $absolutePath, bin2hex(random_bytes(6)));
    }
}
