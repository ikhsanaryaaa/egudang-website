<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Stock Transaction - {{ $transaction->transaction_number }}</title>
    <style>
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 12px; color: #333; }
        .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #333; padding-bottom: 15px; }
        .header h1 { font-size: 20px; margin: 0; }
        .header h2 { font-size: 14px; margin: 5px 0; font-weight: normal; color: #666; }
        .info-table { width: 100%; margin-bottom: 20px; }
        .info-table td { padding: 4px 8px; vertical-align: top; }
        .info-table .label { font-weight: bold; width: 150px; color: #555; }
        .badge { display: inline-block; padding: 2px 10px; border-radius: 4px; color: #fff; font-size: 11px; font-weight: bold; }
        .badge-in { background-color: #22c55e; }
        .badge-out { background-color: #ef4444; }
        .badge-adj { background-color: #f59e0b; }
        table.items { width: 100%; border-collapse: collapse; margin-top: 10px; }
        table.items th { background-color: #f3f4f6; border: 1px solid #d1d5db; padding: 8px; text-align: left; font-size: 11px; text-transform: uppercase; }
        table.items td { border: 1px solid #d1d5db; padding: 8px; }
        table.items tr:nth-child(even) { background-color: #f9fafb; }
        .footer { margin-top: 40px; text-align: center; font-size: 10px; color: #999; border-top: 1px solid #ddd; padding-top: 10px; }
        .section-title { font-size: 14px; font-weight: bold; margin: 20px 0 10px; padding: 5px 0; border-bottom: 1px solid #e5e7eb; }
    </style>
</head>
<body>
    <div class="header">
        <h1>E-Gudang</h1>
        <h2>Stock Transaction Report</h2>
    </div>

    <table class="info-table">
        <tr>
            <td class="label">No. Transaksi</td>
            <td>: {{ $transaction->transaction_number }}</td>
            <td class="label">Type</td>
            <td>: <span class="badge badge-{{ strtolower($transaction->type) }}">{{ $transaction->type }}</span></td>
        </tr>
        <tr>
            <td class="label">Created By</td>
            <td>: {{ $transaction->creator->name ?? '-' }}</td>
            <td class="label">Tanggal</td>
            <td>: {{ $transaction->created_at->format('d M Y, H:i') }}</td>
        </tr>
        @if($transaction->notes)
        <tr>
            <td class="label">Notes</td>
            <td colspan="3">: {{ $transaction->notes }}</td>
        </tr>
        @endif
    </table>

    <div class="section-title">Transaction Items</div>
    <table class="items">
        <thead>
            <tr>
                <th style="width: 30px;">#</th>
                <th>Product</th>
                <th>SKU</th>
                <th style="text-align: center;">Qty</th>
                <th style="text-align: center;">Before</th>
                <th style="text-align: center;">After</th>
            </tr>
        </thead>
        <tbody>
            @foreach($transaction->items as $index => $item)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $item->product->name }}</td>
                <td>{{ $item->product->sku }}</td>
                <td style="text-align: center;">{{ $item->qty }}</td>
                <td style="text-align: center;">{{ $item->before_stock }}</td>
                <td style="text-align: center;">{{ $item->after_stock }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        Generated on {{ now()->format('d M Y, H:i:s') }} | E-Gudang Inventory System
    </div>
</body>
</html>
