<?php

namespace App\Traits;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

trait HandlesImageUploads
{
    /**
     * Upload and resize an image.
     *
     * @param UploadedFile $file The uploaded file instance
     * @param string $path The directory path to store the image (e.g., 'students/photos')
     * @param string $disk The storage disk (e.g., 'public', 'local')
     * @param int $maxWidth The maximum width of the image (default: 800)
     * @param int $maxHeight The maximum height of the image (default: 800)
     * @param int $quality The quality of the saved image (default: 80)
     * @return string The path to the stored image
     */
    public function uploadAndResizeImage(UploadedFile $file, string $path, string $disk = 'public', int $maxWidth = 800, int $maxHeight = 800, int $quality = 80): string
    {
        // Ensure the directory exists
        if (!Storage::disk($disk)->exists($path)) {
            Storage::disk($disk)->makeDirectory($path);
        }

        // Generate a unique filename with webp extension for better compression
        $filename = uniqid() . '_' . time() . '.webp';
        $fullPath = $path . '/' . $filename;

        // Initialize ImageManager with GD driver
        $manager = new ImageManager(new Driver());

        // Read image from uploaded file
        $image = $manager->read($file->getRealPath());

        // Resize down if larger than max width/height, keeping aspect ratio
        $image->scaleDown(width: $maxWidth, height: $maxHeight);

        // Encode as WebP and save to storage
        $encodedImage = $image->toWebp(quality: $quality);
        
        Storage::disk($disk)->put($fullPath, (string) $encodedImage);

        return $fullPath;
    }
}
