<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBookingRequest;
use App\Http\Resources\BookingResource;
use App\Models\Booking;
use App\Models\Room;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

class BookingController extends Controller
{
    public function mine(): JsonResponse
    {
        $bookings = request()->user()->bookings()
            ->with(['room', 'handovers'])
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (Booking $booking) => (new BookingResource($booking))->resolve());

        return response()->json(['data' => $bookings]);
    }

    public function store(StoreBookingRequest $request): JsonResponse
    {
        $data = $request->validated();
        $start = CarbonImmutable::createFromFormat('Y-m-d', $data['start_date']);
        $end = $start->addMonthsNoOverflow((int) $data['duration_months']);
        $path = $request->file('payment_proof')->store('payment-proofs', 'public');

        if (! is_string($path) || $path === '') {
            throw new RuntimeException('Bukti pembayaran gagal disimpan.');
        }

        try {
            $booking = DB::transaction(function () use ($data, $request, $start, $end, $path): Booking {
                $room = Room::query()->whereKey($data['room_id'])->lockForUpdate()->firstOrFail();
                $occupants = (int) $data['occupants'];

                if ($occupants > $room->capacity) {
                    throw ValidationException::withMessages([
                        'occupants' => ['Jumlah penghuni melebihi kapasitas kamar.'],
                    ]);
                }

                if ($room->remainingSlots($start, $end) < $occupants) {
                    throw ValidationException::withMessages([
                        'room_id' => ['Slot kamar tidak mencukupi untuk rentang tanggal tersebut.'],
                    ]);
                }

                return Booking::create([
                    'user_id' => $request->user()->id,
                    'room_id' => $room->id,
                    'start_date' => $start->toDateString(),
                    'duration_months' => (int) $data['duration_months'],
                    'end_date' => $end->toDateString(),
                    'occupants' => $occupants,
                    'payment_proof_path' => $path,
                    'payment_status' => 'menunggu',
                    'status' => 'aktif',
                ])->load(['room', 'handovers']);
            });
        } catch (Throwable $exception) {
            Storage::disk('public')->delete($path);
            throw $exception;
        }

        return (new BookingResource($booking))->response()->setStatusCode(201);
    }
}
