<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class BulkInsertProducts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:bulk-products {count=50 : The number of products to create}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Bulk insert dummy categories and products for testing';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $count = (int) $this->argument('count');
        $this->info("Membuat {$count} produk dummy...");

        // Bikin 5 Kategori Dummy
        $categories = [];
        for ($i = 1; $i <= 5; $i++) {
            $categories[] = \App\Models\Category::firstOrCreate([
                'name' => 'Kategori Dummy ' . $i,
                'slug' => 'kategori-dummy-' . $i,
            ])->id;
        }

        // Bikin Produk pakai insert biar cepat (bypass QR generation, akan digenerate nanti kalau diedit)
        $products = [];
        for ($i = 1; $i <= $count; $i++) {
            $sku = 'DUMMY-' . strtoupper(\Illuminate\Support\Str::random(6)) . '-' . $i;
            $products[] = [
                'category_id' => $categories[array_rand($categories)],
                'sku' => $sku,
                'barcode' => $sku,
                'name' => 'Produk Dummy ' . $i . ' ' . \Illuminate\Support\Str::random(4),
                'brand' => 'Brand Dummy',
                'description' => 'Ini adalah produk dummy untuk testing bulk insert.',
                'stock' => rand(10, 500),
                'minimum_stock' => rand(5, 20),
                'unit' => 'pcs',
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        // Chunk insert per 500 records biar ga overload memory
        $chunks = array_chunk($products, 500);
        foreach ($chunks as $chunk) {
            \App\Models\Product::insert($chunk);
        }

        $this->info("Selesai! {$count} produk berhasil ditambahkan ke database.");
    }
}
