<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Batch Inventory</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; }
        h1 { font-size: 18px; text-align: center; margin-bottom: 5px; }
        .meta { text-align: center; color: #666; margin-bottom: 20px; font-size: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #333; padding: 6px 8px; text-align: left; }
        th { background-color: #f0f0f0; font-weight: bold; }
        .right { text-align: right; }
    </style>
</head>
<body>
    <h1>Laporan Batch Inventory - E-Gudang</h1>
    <div class="meta">Dicetak: {{ $filters['generated_at'] }}</div>

    <table>
        <thead>
            <tr>
                <th>Produk</th>
                <th>Qty Masuk</th>
                <th>Qty Tersisa</th>
                <th>Harga Satuan</th>
                <th>Total Nilai</th>
                <th>Tanggal Diterima</th>
            </tr>
        </thead>
        <tbody>
            @foreach($data as $batch)
            <tr>
                <td>{{ $batch->product->name ?? '-' }}</td>
                <td>{{ $batch->qty_in }}</td>
                <td>{{ $batch->qty_remaining }}</td>
                <td class="right">{{ number_format($batch->unit_cost, 2) }}</td>
                <td class="right">{{ number_format($batch->qty_remaining * $batch->unit_cost, 2) }}</td>
                <td>{{ $batch->received_at->format('Y-m-d H:i') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
