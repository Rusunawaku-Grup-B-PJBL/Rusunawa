<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreHandoverRequest;
use App\Http\Resources\HandoverResource;
use App\Models\Booking;
use App\Models\Handover;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

class HandoverController extends Controller
{
    public function mine(): JsonResponse
    {
        $handovers = Handover::query()
            ->whereHas('booking', fn ($query) => $query->where('user_id', request()->user()->id))
            ->with(['booking.room', 'items'])
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (Handover $handover) => (new HandoverResource($handover))->resolve());

        return response()->json(['data' => $handovers]);
    }

    public function store(StoreHandoverRequest $request): JsonResponse
    {
        $data = $request->validated();
        $items = $data['items'];

        if (collect($items)->contains(fn (array $item) => $item['condition'] === 'rusak')
            && blank($data['notes'] ?? null)) {
            throw ValidationException::withMessages([
                'notes' => ['Catatan wajib diisi jika ada barang yang rusak.'],
            ]);
        }

        $itemNames = collect($items)->pluck('item_name')->sort()->values()->all();
        $expectedNames = collect(StoreHandoverRequest::ITEMS)->sort()->values()->all();

        if ($itemNames !== $expectedNames) {
            throw ValidationException::withMessages([
                'items' => ['Daftar harus memuat tepat enam item pemeriksaan yang ditentukan.'],
            ]);
        }

        $photoPath = $request->hasFile('photo')
            ? $request->file('photo')->store('handovers/photos', 'public')
            : null;
        $signaturePath = $request->file('signature')->store('handovers/signatures', 'public');

        if (($request->hasFile('photo') && (! is_string($photoPath) || $photoPath === ''))
            || ! is_string($signaturePath) || $signaturePath === '') {
            if ($photoPath) {
                Storage::disk('public')->delete($photoPath);
            }
            throw new RuntimeException('Berkas serah terima gagal disimpan.');
        }

        try {
            $handover = DB::transaction(function () use ($request, $data, $items, $photoPath, $signaturePath): Handover {
                $today = CarbonImmutable::today();
                $bookings = Booking::query()
                    ->where('user_id', $request->user()->id)
                    ->where('status', 'aktif')
                    ->where('payment_status', 'terverifikasi');

                if ($data['type'] === 'checkin') {
                    $bookings
                        ->whereDate('start_date', '<=', $today->toDateString())
                        ->whereDate('end_date', '>', $today->toDateString());
                } else {
                    $bookings
                        ->whereHas('handovers', fn ($query) => $query->where('type', 'checkin'))
                        ->whereDoesntHave('handovers', fn ($query) => $query->where('type', 'checkout'));
                }

                $booking = $bookings
                    ->orderByDesc('start_date')
                    ->lockForUpdate()
                    ->first();

                if (! $booking) {
                    throw ValidationException::withMessages([
                        'booking' => [
                            $data['type'] === 'checkin'
                                ? 'Check-in hanya dapat dilakukan pada masa sewa aktif dengan pembayaran terverifikasi.'
                                : 'Check-out hanya dapat dilakukan setelah check-in pada booking terverifikasi.',
                        ],
                    ]);
                }

                $hasCheckin = $booking->handovers()->where('type', 'checkin')->exists();
                $hasCheckout = $booking->handovers()->where('type', 'checkout')->exists();

                if ($data['type'] === 'checkin' && $hasCheckin) {
                    throw ValidationException::withMessages([
                        'type' => ['Check-in untuk booking ini sudah dilakukan.'],
                    ]);
                }

                if ($data['type'] === 'checkout' && ! $hasCheckin) {
                    throw ValidationException::withMessages([
                        'type' => ['Check-out hanya dapat dilakukan setelah check-in.'],
                    ]);
                }

                if ($data['type'] === 'checkout' && $hasCheckout) {
                    throw ValidationException::withMessages([
                        'type' => ['Check-out untuk booking ini sudah dilakukan.'],
                    ]);
                }

                $handover = $booking->handovers()->create([
                    'type' => $data['type'],
                    'notes' => $data['notes'] ?? null,
                    'photo_path' => $photoPath,
                    'signature_path' => $signaturePath,
                ]);

                $handover->items()->createMany($items);

                if ($data['type'] === 'checkout') {
                    $booking->update(['status' => 'selesai']);
                }

                return $handover->load(['booking.room', 'items']);
            });
        } catch (Throwable $exception) {
            if ($photoPath) {
                Storage::disk('public')->delete($photoPath);
            }
            Storage::disk('public')->delete($signaturePath);
            throw $exception;
        }

        return (new HandoverResource($handover))->response()->setStatusCode(201);
    }
}
