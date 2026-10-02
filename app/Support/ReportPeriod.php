<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ReportPeriod
{
    /**
     * Tentukan rentang waktu laporan dari filter yang dikirim.
     *
     * @return array{label: string, from: Carbon|null, to: Carbon|null} from/to null berarti seluruh waktu
     */
    public static function resolve(Request $request, string $default = 'bulan'): array
    {
        $period = $request->string('period')->value();
        $period = in_array($period, ['semua', 'hari', 'minggu', 'bulan', 'custom'], true) ? $period : $default;

        $custom = $period === 'custom'
            ? $request->validate([
                'start_date' => ['required', 'date'],
                'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            ])
            : [];

        return match ($period) {
            'semua' => ['label' => 'Semua waktu', 'from' => null, 'to' => null],
            'hari' => ['label' => 'Hari ini', 'from' => now()->startOfDay(), 'to' => now()],
            'minggu' => ['label' => 'Minggu ini', 'from' => now()->startOfWeek(), 'to' => now()],
            'custom' => [
                'label' => "{$custom['start_date']} s.d. {$custom['end_date']}",
                'from' => Carbon::parse($custom['start_date'])->startOfDay(),
                'to' => Carbon::parse($custom['end_date'])->endOfDay(),
            ],
            default => ['label' => 'Bulan ini', 'from' => now()->startOfMonth(), 'to' => now()],
        };
    }
}
