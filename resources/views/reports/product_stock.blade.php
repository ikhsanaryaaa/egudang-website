<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Stok Produk</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; }
        h1 { font-size: 18px; text-align: center; margin-bottom: 5px; }
        .meta { text-align: center; color: #666; margin-bottom: 20px; font-size: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #333; padding: 6px 8px; text-align: left; }
        th { background-color: #f0f0f0; font-weight: bold; }
        .low { color: #dc2626; font-weight: bold; }
    </style>
</head>
<body>
    <h1>Laporan Stok Produk - E-Gudang</h1>
    <div class="meta">Dicetak: {{ $filters['generated_at'] }}</div>

    <table>
        <thead>
            <tr>
                <th>SKU</th>
                <th>Nama Produk</th>
                <th>Kategori</th>
                <th>Stok</th>
                <th>Minimum</th>
                <th>Satuan</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($data as $product)
            <tr>
                <td>{{ $product->sku }}</td>
                <td>{{ $product->name }}</td>
                <td>{{ $product->category->name ?? '-' }}</td>
                <td>{{ $product->stock }}</td>
                <td>{{ $product->minimum_stock }}</td>
                <td>{{ $product->unit }}</td>
                <td class="{{ $product->stock <= $product->minimum_stock ? 'low' : '' }}">
                    {{ $product->stock <= $product->minimum_stock ? 'LOW STOCK' : 'OK' }}
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
