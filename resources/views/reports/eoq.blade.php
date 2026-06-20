<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Perhitungan EOQ</title>
    <style>
        body { font-family: sans-serif; font-size: 11px; }
        h1 { font-size: 18px; text-align: center; margin-bottom: 5px; }
        .meta { text-align: center; color: #666; margin-bottom: 20px; font-size: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #333; padding: 5px 6px; text-align: left; }
        th { background-color: #f0f0f0; font-weight: bold; }
        .right { text-align: right; }
    </style>
</head>
<body>
    <h1>Laporan Perhitungan EOQ - E-Gudang</h1>
    <div class="meta">
        Periode: {{ $filters['date_from'] ?? '-' }} s/d {{ $filters['date_to'] ?? '-' }}<br>
        Dicetak: {{ $filters['generated_at'] }}
    </div>

    <table>
        <thead>
            <tr>
                <th>Tanggal</th>
                <th>Periode</th>
                <th>Basis</th>
                <th>Barang</th>
                <th class="right">Permintaan</th>
                <th class="right">EOQ</th>
                <th class="right">ROP</th>
                <th class="right">Total Biaya</th>
            </tr>
        </thead>
        <tbody>
            @forelse($data as $row)
            <tr>
                <td>{{ $row->calculation_date?->format('d/m/Y') }}</td>
                <td>{{ $row->period_label }}</td>
                <td>{{ ucfirst($row->period_type) }}</td>
                <td>{{ $row->product->name ?? '-' }}</td>
                <td class="right">{{ number_format($row->demand) }}</td>
                <td class="right">{{ number_format($row->eoq, 2) }}</td>
                <td class="right">{{ number_format($row->rop, 2) }}</td>
                <td class="right">Rp {{ number_format($row->total_cost, 2) }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="8" style="text-align:center;">Tidak ada data perhitungan pada rentang tanggal terpilih.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
