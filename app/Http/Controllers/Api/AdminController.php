<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAnnouncementRequest;
use App\Http\Requests\UpdateComplaintTicketRequest;
use App\Http\Requests\UpdateDamageReportRequest;
use App\Http\Requests\UpdatePaymentRequest;
use App\Http\Resources\BookingResource;
use App\Http\Resources\ComplaintTicketResource;
use App\Http\Resources\DamageReportResource;
use App\Models\Announcement;
use App\Models\Booking;
use App\Models\ComplaintTicket;
use App\Models\DamageReport;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AdminController extends Controller
{
    public function bookings(): JsonResponse
    {
        $bookings = Booking::query()
            ->with(['room', 'handovers', 'user:id,name,nim'])
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (Booking $booking) => (new BookingResource($booking))->resolve());

        return response()->json(['data' => $bookings]);
    }

    public function damageReports(): JsonResponse
    {
        $reports = DamageReport::query()
            ->with(['room', 'logs'])
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (DamageReport $report) => (new DamageReportResource($report))->resolve());

        return response()->json(['data' => $reports]);
    }

    public function complaintTickets(): JsonResponse
    {
        $tickets = ComplaintTicket::query()
            ->with(['user:id,name,nim', 'logs'])
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (ComplaintTicket $ticket) => (new ComplaintTicketResource($ticket))->resolve());

        return response()->json(['data' => $tickets]);
    }

    public function updateComplaintTicket(
        UpdateComplaintTicketRequest $request,
        ComplaintTicket $complaintTicket,
    ): JsonResponse {
        $data = $request->validated();
        DB::transaction(function () use ($request, $data, $complaintTicket): void {
            $complaintTicket->update(['status' => $data['status']]);
            $complaintTicket->logs()->create([
                'author_id' => $request->user()->id,
                'status' => $data['status'],
                'note' => $data['note'] ?? null,
            ]);
        });
        $complaintTicket->load(['user:id,name,nim', 'logs']);

        return response()->json([
            'message' => 'Status keluhan berhasil diperbarui.',
            'data' => (new ComplaintTicketResource($complaintTicket))->resolve(),
        ]);
    }

    public function updatePayment(UpdatePaymentRequest $request, Booking $booking): JsonResponse
    {
        $paymentStatus = $request->validated('payment_status');
        $booking = DB::transaction(function () use ($booking, $paymentStatus): Booking {
            $booking = Booking::query()->lockForUpdate()->findOrFail($booking->getKey());

            if ($booking->payment_status !== 'menunggu' || $booking->status !== 'aktif') {
                throw ValidationException::withMessages([
                    'payment_status' => ['Booking ini sudah diputuskan dan statusnya tidak dapat diubah lagi.'],
                ]);
            }

            $updates = ['payment_status' => $paymentStatus];
            if ($paymentStatus === 'ditolak') {
                $updates['status'] = 'dibatalkan';
            }

            $booking->update($updates);

            return $booking->load(['room', 'handovers']);
        });

        return response()->json([
            'message' => 'Status pembayaran berhasil diperbarui.',
            'data' => (new BookingResource($booking))->resolve(),
        ]);
    }

    public function updateDamageReport(
        UpdateDamageReportRequest $request,
        DamageReport $damageReport,
    ): JsonResponse {
        $data = $request->validated();
        $damageReport->update(array_intersect_key($data, array_flip(['status', 'technician_name'])));

        $damageReport->logs()->create([
            'status' => $damageReport->status,
            'note' => $data['note'] ?? null,
        ]);

        $damageReport->load(['room', 'logs']);

        return response()->json([
            'message' => 'Laporan kerusakan berhasil diperbarui.',
            'data' => (new DamageReportResource($damageReport))->resolve(),
        ]);
    }

    public function storeAnnouncement(StoreAnnouncementRequest $request): JsonResponse
    {
        $announcement = Announcement::create($request->validated());

        return response()->json([
            'message' => 'Pengumuman berhasil diterbitkan.',
            'data' => [
                'id' => $announcement->id,
                'title' => $announcement->title,
                'body' => $announcement->body,
                'published_at' => $announcement->published_at?->toISOString(),
            ],
        ], 201);
    }
}
