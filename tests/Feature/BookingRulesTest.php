<?php

namespace Tests\Feature;

use App\Http\Requests\StoreHandoverRequest;
use App\Models\Booking;
use App\Models\Room;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class BookingRulesTest extends TestCase
{
    use RefreshDatabase;

    public function test_booking_payment_decision_is_terminal(): void
    {
        $resident = $this->user();
        $admin = $this->user('admin');
        $room = $this->room();
        $verifiedBooking = $this->booking($resident, $room);
        $rejectedBooking = $this->booking($resident, $room);

        Sanctum::actingAs($admin);
        $this->patchJson("/api/admin/bookings/{$verifiedBooking->id}/payment", [
            'payment_status' => 'terverifikasi',
        ])->assertOk();

        $this->patchJson("/api/admin/bookings/{$verifiedBooking->id}/payment", [
            'payment_status' => 'ditolak',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('payment_status');

        $this->patchJson("/api/admin/bookings/{$rejectedBooking->id}/payment", [
            'payment_status' => 'ditolak',
        ])->assertOk();

        $this->patchJson("/api/admin/bookings/{$rejectedBooking->id}/payment", [
            'payment_status' => 'terverifikasi',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('payment_status');

        $this->assertDatabaseHas('bookings', [
            'id' => $verifiedBooking->id,
            'payment_status' => 'terverifikasi',
            'status' => 'aktif',
        ]);
        $this->assertDatabaseHas('bookings', [
            'id' => $rejectedBooking->id,
            'payment_status' => 'ditolak',
            'status' => 'dibatalkan',
        ]);
    }

    public function test_pending_booking_holds_room_capacity_until_rejected(): void
    {
        Storage::fake('public');
        $resident = $this->user();
        $admin = $this->user('admin');
        $room = $this->room(capacity: 1);
        $start = CarbonImmutable::today()->addDays(3);
        $booking = $this->booking($resident, $room, $start);

        Sanctum::actingAs($resident);
        $this->post('/api/bookings', $this->bookingPayload($room, $start), [
            'Accept' => 'application/json',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('room_id');

        Sanctum::actingAs($admin);
        $this->patchJson("/api/admin/bookings/{$booking->id}/payment", [
            'payment_status' => 'ditolak',
        ])->assertOk();

        Sanctum::actingAs($resident);
        $this->post('/api/bookings', $this->bookingPayload($room, $start), [
            'Accept' => 'application/json',
        ])->assertCreated()
            ->assertJsonPath('data.payment_status', 'menunggu')
            ->assertJsonPath('data.status', 'aktif');
    }

    public function test_handover_requires_verified_booking_and_active_rental_dates(): void
    {
        Storage::fake('public');
        $resident = $this->user();
        $admin = $this->user('admin');
        $room = $this->room();
        $booking = $this->booking($resident, $room);

        Sanctum::actingAs($resident);
        $this->post('/api/handovers', $this->handoverPayload('checkin'), [
            'Accept' => 'application/json',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('booking');

        Sanctum::actingAs($admin);
        $this->patchJson("/api/admin/bookings/{$booking->id}/payment", [
            'payment_status' => 'terverifikasi',
        ])->assertOk();

        Sanctum::actingAs($resident);
        $this->post('/api/handovers', $this->handoverPayload('checkin'), [
            'Accept' => 'application/json',
        ])->assertCreated()
            ->assertJsonPath('data.type', 'checkin');

        $this->post('/api/handovers', $this->handoverPayload('checkout'), [
            'Accept' => 'application/json',
        ])->assertCreated()
            ->assertJsonPath('data.type', 'checkout');

        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'status' => 'selesai',
        ]);
    }

    public function test_checkin_is_not_allowed_before_booking_start_date(): void
    {
        Storage::fake('public');
        $resident = $this->user();
        $admin = $this->user('admin');
        $room = $this->room();
        $booking = $this->booking($resident, $room, CarbonImmutable::today()->addDay());

        Sanctum::actingAs($admin);
        $this->patchJson("/api/admin/bookings/{$booking->id}/payment", [
            'payment_status' => 'terverifikasi',
        ])->assertOk();

        Sanctum::actingAs($resident);
        $this->post('/api/handovers', $this->handoverPayload('checkin'), [
            'Accept' => 'application/json',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('booking');
    }

    public function test_damage_report_requires_verified_booking_during_rental(): void
    {
        Storage::fake('public');
        $resident = $this->user();
        $admin = $this->user('admin');
        $room = $this->room();
        $booking = $this->booking($resident, $room);
        $payload = [
            'category' => 'air',
            'description' => 'Keran kamar mandi bocor.',
        ];

        Sanctum::actingAs($resident);
        $this->postJson('/api/damage-reports', $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('booking');

        Sanctum::actingAs($admin);
        $this->patchJson("/api/admin/bookings/{$booking->id}/payment", [
            'payment_status' => 'terverifikasi',
        ])->assertOk();

        Sanctum::actingAs($resident);
        $response = $this->postJson('/api/damage-reports', $payload)
            ->assertCreated();

        $this->assertDatabaseHas('damage_reports', [
            'id' => $response->json('data.id'),
            'room_id' => $room->id,
        ]);
    }

    private function user(string $role = 'penghuni'): User
    {
        $id = uniqid();

        return User::create([
            'name' => $role === 'admin' ? 'Admin Rusunawa' : 'Budi Santoso',
            'nim' => $role === 'admin' ? null : 'nim-'.$id,
            'email' => $role.'-'.$id.'@ubbg.ac.id',
            'phone' => '081234567890',
            'password' => 'password-uji',
            'role' => $role,
        ]);
    }

    private function room(int $capacity = 4): Room
    {
        return Room::create([
            'code' => 'A-'.random_int(100, 999),
            'floor' => 1,
            'gender' => 'putra',
            'capacity' => $capacity,
            'deposit' => 500000,
        ]);
    }

    private function booking(User $resident, Room $room, ?CarbonImmutable $start = null): Booking
    {
        $start ??= CarbonImmutable::today();

        return Booking::create([
            'user_id' => $resident->id,
            'room_id' => $room->id,
            'start_date' => $start->toDateString(),
            'duration_months' => 1,
            'end_date' => $start->addMonthNoOverflow()->toDateString(),
            'occupants' => 1,
            'payment_proof_path' => 'payment-proofs/bukti.pdf',
        ]);
    }

    private function bookingPayload(Room $room, CarbonImmutable $start): array
    {
        return [
            'room_id' => $room->id,
            'start_date' => $start->toDateString(),
            'duration_months' => 1,
            'occupants' => 1,
            'payment_proof' => UploadedFile::fake()->create('payment.pdf', 100, 'application/pdf'),
        ];
    }

    private function handoverPayload(string $type): array
    {
        return [
            'type' => $type,
            'signature' => UploadedFile::fake()->create('signature.png', 100, 'image/png'),
            'items' => array_map(
                fn (string $itemName): array => ['item_name' => $itemName, 'condition' => 'baik'],
                StoreHandoverRequest::ITEMS,
            ),
        ];
    }
}
