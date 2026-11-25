<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDamageReportRequest;
use App\Http\Resources\DamageReportResource;
use App\Models\DamageReport;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

class DamageReportController extends Controller
{
    public function mine(): JsonResponse
    {
        $reports = request()->user()->damageReports()
            ->with(['room', 'logs'])
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (DamageReport $report) => (new DamageReportResource($report))->resolve());

        return response()->json(['data' => $reports]);
    }

    public function store(StoreDamageReportRequest $request): JsonResponse
    {
        $today = CarbonImmutable::today();
        $path = $request->hasFile('photo')
            ? $request->file('photo')->store('damage-reports', 'public')
            : null;

        if ($request->hasFile('photo') && (! is_string($path) || $path === '')) {
            throw new RuntimeException('Foto laporan gagal disimpan.');
        }

        try {
            $report = DB::transaction(function () use ($request, $today, $path): DamageReport {
                $booking = $request->user()->bookings()
                    ->where('status', 'aktif')
                    ->where('payment_status', 'terverifikasi')
                    ->whereDate('start_date', '<=', $today->toDateString())
                    ->whereDate('end_date', '>', $today->toDateString())
                    ->with('room')
                    ->orderByDesc('start_date')
                    ->lockForUpdate()
                    ->first();

                if (! $booking) {
                    throw ValidationException::withMessages([
                        'booking' => [
                            'Laporan hanya dapat dibuat jika memiliki booking terverifikasi yang sedang berlangsung.',
                        ],
                    ]);
                }

                $report = DamageReport::create([
                    'user_id' => $request->user()->id,
                    'room_id' => $booking->room_id,
                    'category' => $request->validated('category'),
                    'description' => $request->validated('description'),
                    'photo_path' => $path,
                    'status' => 'dilaporkan',
                ]);

                $report->logs()->create([
                    'status' => 'dilaporkan',
                    'note' => 'Laporan diterima.',
                ]);

                return $report->load(['room', 'logs']);
            });
        } catch (Throwable $exception) {
            if ($path) {
                Storage::disk('public')->delete($path);
            }

            throw $exception;
        }

        return (new DamageReportResource($report))->response()->setStatusCode(201);
    }
}
