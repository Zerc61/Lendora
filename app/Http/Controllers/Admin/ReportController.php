<?php
// app/Http/Controllers/Admin/ReportController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\User;
use App\Services\ReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ReportController extends Controller
{
    public function index(Request $request, ReportService $service)
    {
        $validated = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'asset_id' => ['nullable', 'integer', 'exists:assets,id'],
        ]);

        $from = isset($validated['from']) ? Carbon::parse($validated['from'])->startOfDay() : now()->subDays(29)->startOfDay();
        $to = isset($validated['to']) ? Carbon::parse($validated['to'])->endOfDay() : now()->endOfDay();
        $userId = $validated['user_id'] ?? null;
        $assetId = $validated['asset_id'] ?? null;

        return view('admin.reports.index', [
            'from' => $from,
            'to' => $to,
            'summary' => $service->summary($from, $to, $userId, $assetId),
            'borrowings' => $service->borrowingsQuery($from, $to, $userId, $assetId)
                ->paginate(15)
                ->withQueryString(),
            'utilization' => $service->utilization($from, $to, $assetId),
            'maintenanceCosts' => $service->maintenanceCosts($from, $to),
            'users' => User::orderBy('name')->get(['id', 'name']),
            'assets' => Asset::orderBy('asset_code')->get(['id', 'asset_code']),
        ]);
    }

    /**
     * Export CSV (PDF 4M) — stream + chunk, hemat memori.
     * BOM UTF-8 disisipkan agar karakter tampil benar di Excel.
     */
    public function export(Request $request, ReportService $service)
    {
        $validated = $request->validate([
            'type' => ['required', 'in:borrowings,utilization,maintenance-cost'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
            'user_id' => ['nullable', 'integer'],
            'asset_id' => ['nullable', 'integer'],
        ]);

        $from = isset($validated['from']) ? Carbon::parse($validated['from'])->startOfDay() : now()->subDays(29)->startOfDay();
        $to = isset($validated['to']) ? Carbon::parse($validated['to'])->endOfDay() : now()->endOfDay();
        $userId = $validated['user_id'] ?? null;
        $assetId = $validated['asset_id'] ?? null;
        $type = $validated['type'];

        $filename = "lendora-{$type}-{$from->format('Ymd')}-{$to->format('Ymd')}.csv";

        return response()->streamDownload(function () use ($type, $service, $from, $to, $userId, $assetId) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // UTF-8 BOM

            if ($type === 'borrowings') {
                fputcsv($out, ['Kode', 'Peminjam', 'Unit', 'Status', 'Check-out', 'Batas Kembali', 'Dikembalikan', 'Tujuan']);

                $service->borrowingsQuery($from, $to, $userId, $assetId)
                    ->chunk(500, function ($rows) use ($out) {
                        foreach ($rows as $b) {
                            fputcsv($out, [
                                $b->code,
                                $b->borrower->name,
                                $b->items->pluck('asset.asset_code')->implode('; '),
                                $b->status->value,
                                $b->checked_out_at?->format('Y-m-d H:i'),
                                $b->due_at?->format('Y-m-d H:i'),
                                $b->returned_at?->format('Y-m-d H:i'),
                                $b->purpose,
                            ]);
                        }
                    });
            } elseif ($type === 'utilization') {
                fputcsv($out, ['Asset Code', 'Tipe', 'Jumlah Peminjaman', 'Total Hari Dipinjam', 'Utilisasi %']);

                foreach ($service->utilization($from, $to, $assetId) as $row) {
                    fputcsv($out, [$row->asset_code, $row->type_name, $row->borrow_count, $row->days_out, $row->utilization_pct]);
                }
            } else {
                fputcsv($out, ['Asset Code', 'Tipe', 'Jumlah Tiket', 'Total Biaya (Rp)']);

                foreach ($service->maintenanceCosts($from, $to) as $row) {
                    fputcsv($out, [$row->asset_code, $row->type_name, $row->ticket_count, $row->total_cost]);
                }
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
