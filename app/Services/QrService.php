<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Facades\Storage;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

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
        $directory = 'public/qrcodes';

        if (!Storage::exists($directory)) {
            Storage::makeDirectory($directory);
        }

        $filename = 'qr-' . $product->sku . '.svg';
        $path = $directory . '/' . $filename;

        $qrImage = QrCode::format('svg')
            ->size(300)
            ->margin(2)
            ->generate($product->sku);

        Storage::put($path, $qrImage);

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
