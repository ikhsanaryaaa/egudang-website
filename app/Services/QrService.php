<?php

namespace App\Services;

use App\Models\Product;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Support\Facades\Storage;

class QrService
{
    /**
     * Generate a QR Code for a product and save it to storage.
     * The QR code contains the product SKU.
     *
     * @param Product $product
     * @return string The relative path to the saved QR code image.
     */
    public function generateForProduct(Product $product): string
    {
        $directory = 'qrcodes';

        if (!Storage::disk('public')->exists($directory)) {
            Storage::disk('public')->makeDirectory($directory);
        }

        $filename = 'qr-' . $product->sku . '.png';
        $path = $directory . '/' . $filename;

        $result = Builder::create()
            ->writer(new PngWriter())
            ->data($product->sku)
            ->encoding(new Encoding('UTF-8'))
            ->errorCorrectionLevel(ErrorCorrectionLevel::High)
            ->size(300)
            ->margin(10)
            ->build();

        Storage::disk('public')->put($path, $result->getString());

        return 'qrcodes/' . $filename;
    }

    /**
     * Delete a QR Code file from storage.
     *
     * @param string|null $qrCodePath
     * @return void
     */
    public function delete(?string $qrCodePath): void
    {
        if ($qrCodePath && Storage::disk('public')->exists($qrCodePath)) {
            Storage::disk('public')->delete($qrCodePath);
        }
    }

    /**
     * Regenerate QR Code for a product (delete old, create new).
     *
     * @param Product $product
     * @return string
     */
    public function regenerateForProduct(Product $product): string
    {
        $this->delete($product->qr_code_path);
        return $this->generateForProduct($product);
    }
}
