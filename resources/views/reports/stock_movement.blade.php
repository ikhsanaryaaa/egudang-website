<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Pergerakan Stok</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; }
        h1 { font-size: 18px; text-align: center; margin-bottom: 5px; }
        .meta { text-align: center; color: #666; margin-bottom: 20px; font-size: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #333; padding: 6px 8px; text-align: left; }
        th { background-color: #f0f0f0; font-weight: bold; }
        .in { color: #16a34a; }
        .out { color: #dc2626; }
        .adj { color: #ca8a04; }
    </style>
</head>
<body>
    <h1>Laporan Pergerakan Stok - E-Gudang</h1>
    <div class="meta">
        Periode: {{ $filters['date_from'] ?? 'Semua' }} s/d {{ $filters['date_to'] ?? 'Semua' }} |
        Dicetak: {{ $filters['generated_at'] }}
    </div>

    <table>
        <thead>
            <tr>
                <th>No. Transaksi</th>
                <th>Produk</th>
                <th>Tipe</th>
                <th>Qty</th>
                <th>Stok Sebelum</th>
                <th>Stok Sesudah</th>
                <th>User</th>
                <th>Tanggal</th>
            </tr>
        </thead>
        <tbody>
            @foreach($data as $item)
            <tr>
                <td>{{ $item->transaction->transaction_number ?? '-' }}</td>
                <td>{{ $item->product->name ?? '-' }}</td>
                <td class="{{ strtolower($item->transaction->type ?? '') }}">
                    {{ $item->transaction->type ?? '-' }}
                </td>
                <td>{{ $item->qty }}</td>
                <td>{{ $item->before_stock }}</td>
                <td>{{ $item->after_stock }}</td>
                <td>{{ $item->transaction->creator->name ?? '-' }}</td>
                <td>{{ $item->created_at->format('Y-m-d H:i') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
