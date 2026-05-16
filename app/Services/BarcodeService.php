<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Facades\Storage;
use Picqer\Barcode\BarcodeGeneratorPNG;

class BarcodeService
{
    /**
     * Generate a barcode image for a product and save it to storage.
     *
     * @param Product $product
     * @return string The relative path to the saved barcode image.
     */
    public function generateForProduct(Product $product): string
    {
        $directory = 'barcodes';

        if (!Storage::disk('public')->exists($directory)) {
            Storage::disk('public')->makeDirectory($directory);
        }

        $filename = 'barcode-' . $product->sku . '.png';
        $path = $directory . '/' . $filename;

        $generator = new BarcodeGeneratorPNG();
        $barcodeData = $product->barcode;

        $barcodeRaw = $generator->getBarcode($barcodeData, $generator::TYPE_CODE_128, 2, 60);

        // Add white background with balanced padding
        $barcodeSrc = imagecreatefromstring($barcodeRaw);
        $srcWidth = imagesx($barcodeSrc);
        $srcHeight = imagesy($barcodeSrc);
        $pad = 15;
        $canvas = imagecreatetruecolor($srcWidth + ($pad * 2), $srcHeight + ($pad * 2));
        $white = imagecolorallocate($canvas, 255, 255, 255);
        imagefill($canvas, 0, 0, $white);
        imagecopy($canvas, $barcodeSrc, $pad, $pad, 0, 0, $srcWidth, $srcHeight);

        ob_start();
        imagepng($canvas);
        $image = ob_get_clean();
        imagedestroy($barcodeSrc);
        imagedestroy($canvas);

        Storage::disk('public')->put($path, $image);

        return 'barcodes/' . $filename;
    }

    /**
     * Delete a barcode image from storage.
     *
     * @param string|null $barcodePath
     * @return void
     */
    public function delete(?string $barcodePath): void
    {
        if ($barcodePath && Storage::disk('public')->exists($barcodePath)) {
            Storage::disk('public')->delete($barcodePath);
        }
    }

    /**
     * Regenerate barcode for a product (delete old, create new).
     *
     * @param Product $product
     * @return string
     */
    public function regenerateForProduct(Product $product): string
    {
        $this->delete($product->barcode_image_path);
        return $this->generateForProduct($product);
    }
}
