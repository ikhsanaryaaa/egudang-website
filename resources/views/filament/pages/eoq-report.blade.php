<x-filament-panels::page>
    <form wire:submit.prevent>
        {{ $this->form }}
    </form>

    <div class="mt-6">
        <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="fi-section-content p-6">
                <h3 class="text-base font-semibold mb-4">Hasil Perhitungan EOQ</h3>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left border-collapse">
                        <thead>
                            <tr class="border-b border-gray-200 dark:border-gray-700">
                                <th class="px-3 py-2">Tanggal</th>
                                <th class="px-3 py-2">Periode</th>
                                <th class="px-3 py-2">Basis</th>
                                <th class="px-3 py-2">Barang</th>
                                <th class="px-3 py-2 text-right">Permintaan</th>
                                <th class="px-3 py-2 text-right">EOQ</th>
                                <th class="px-3 py-2 text-right">ROP</th>
                                <th class="px-3 py-2 text-right">Total Biaya</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($this->getRecords() as $row)
                                <tr class="border-b border-gray-100 dark:border-gray-800">
                                    <td class="px-3 py-2">{{ $row->calculation_date?->format('d/m/Y') }}</td>
                                    <td class="px-3 py-2">{{ $row->period_label }}</td>
                                    <td class="px-3 py-2">{{ ucfirst($row->period_type) }}</td>
                                    <td class="px-3 py-2">{{ $row->product->name ?? '-' }}</td>
                                    <td class="px-3 py-2 text-right">{{ number_format($row->demand) }}</td>
                                    <td class="px-3 py-2 text-right">{{ number_format($row->eoq, 2) }}</td>
                                    <td class="px-3 py-2 text-right">{{ number_format($row->rop, 2) }}</td>
                                    <td class="px-3 py-2 text-right">Rp {{ number_format($row->total_cost, 2) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="px-3 py-6 text-center text-gray-500">
                                        Tidak ada data perhitungan pada rentang tanggal terpilih.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>
