<?php

namespace Tests\Feature;

use App\Models\Announcement;
use App\Models\Booking;
use App\Models\ComplaintTicket;
use App\Models\Room;
use App\Models\SupportConversation;
use App\Models\SupportMessage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AnnouncementReadAndSupportTest extends TestCase
{
    use RefreshDatabase;

    public function test_announcement_read_status_is_saved_per_user(): void
    {
        $resident = $this->user();
        $otherResident = $this->user('penghuni', 'other@ubbg.ac.id');
        $announcement = Announcement::create([
            'title' => 'Pemeliharaan',
            'body' => 'Akan dilakukan pemeliharaan fasilitas.',
            'published_at' => now()->subMinute(),
        ]);

        Sanctum::actingAs($resident);
        $this->getJson('/api/announcements')
            ->assertOk()
            ->assertJsonPath('data.0.is_read', false);

        $this->postJson("/api/announcements/{$announcement->id}/read")
            ->assertOk()
            ->assertJsonPath('data.is_read', true);

        $this->getJson('/api/announcements')
            ->assertOk()
            ->assertJsonPath('data.0.is_read', true);

        Sanctum::actingAs($otherResident);
        $this->getJson('/api/announcements')
            ->assertOk()
            ->assertJsonPath('data.0.is_read', false);
    }

    public function test_resident_and_admin_can_exchange_support_messages(): void
    {
        $resident = $this->user();
        $admin = $this->user('admin', 'admin@ubbg.ac.id');

        Sanctum::actingAs($resident);
        $this->getJson('/api/support/messages')
            ->assertOk()
            ->assertJsonPath('data.conversation_id', null)
            ->assertJsonCount(0, 'data.messages');

        $this->postJson('/api/support/messages', ['body' => 'Mohon bantuan fasilitas.'])
            ->assertCreated()
            ->assertJsonPath('data.sender_role', 'penghuni');

        $conversation = SupportConversation::query()
            ->where('user_id', $resident->id)
            ->firstOrFail();

        Sanctum::actingAs($admin);
        $this->getJson('/api/admin/support/conversations')
            ->assertOk()
            ->assertJsonPath('data.0.unread_count', 1);

        $this->getJson("/api/admin/support/conversations/{$conversation->id}")
            ->assertOk()
            ->assertJsonPath('data.messages.0.body', 'Mohon bantuan fasilitas.');

        $this->postJson(
            "/api/admin/support/conversations/{$conversation->id}/messages",
            ['body' => 'Laporan diterima, kami akan menindaklanjuti.'],
        )
            ->assertCreated()
            ->assertJsonPath('data.sender_role', 'admin');

        $residentMessage = SupportMessage::query()->firstOrFail();
        $this->assertNotNull($residentMessage->fresh()->read_at);

        Sanctum::actingAs($resident);
        $this->getJson('/api/support/messages')
            ->assertOk()
            ->assertJsonCount(2, 'data.messages')
            ->assertJsonPath('data.messages.1.sender_role', 'admin');
    }

    public function test_resident_complaint_can_be_tracked_and_updated_by_admin(): void
    {
        $resident = $this->user();
        $admin = $this->user('admin', 'admin@ubbg.ac.id');

        Sanctum::actingAs($resident);
        $response = $this->postJson('/api/complaints', [
            'title' => 'Lampu koridor mati',
            'category' => 'fasilitas',
            'description' => 'Lampu koridor lantai dua mati.',
        ])
            ->assertCreated()
            ->assertJsonPath('data.status', 'dikirim');

        $ticketId = $response->json('data.id');
        $ticket = ComplaintTicket::findOrFail($ticketId);
        $this->assertCount(1, $ticket->logs);

        Sanctum::actingAs($admin);
        $this->getJson('/api/admin/complaints')
            ->assertOk()
            ->assertJsonPath('data.0.user.name', 'Budi Santoso');

        $this->patchJson("/api/admin/complaints/{$ticketId}", [
            'status' => 'diproses',
            'note' => 'Petugas ditugaskan.',
        ])
            ->assertOk()
            ->assertJsonPath('data.status_label', 'Diproses')
            ->assertJsonPath('data.logs.1.note', 'Petugas ditugaskan.');

        Sanctum::actingAs($resident);
        $this->getJson('/api/complaints/mine')
            ->assertOk()
            ->assertJsonPath('data.0.status', 'diproses');
    }

    public function test_admin_booking_list_includes_resident_details_for_verification(): void
    {
        $resident = $this->user();
        $admin = $this->user('admin', 'admin@ubbg.ac.id');
        $room = Room::create([
            'code' => 'A-101',
            'floor' => 1,
            'gender' => 'putra',
            'capacity' => 4,
            'deposit' => 500000,
        ]);
        $booking = Booking::create([
            'user_id' => $resident->id,
            'room_id' => $room->id,
            'start_date' => now()->toDateString(),
            'duration_months' => 1,
            'end_date' => now()->addMonth()->toDateString(),
            'occupants' => 1,
            'payment_proof_path' => 'payment-proofs/bukti.png',
        ]);

        Sanctum::actingAs($admin);
        $response = $this->getJson('/api/admin/bookings')
            ->assertOk()
            ->assertJsonPath('data.0.id', $booking->id)
            ->assertJsonPath('data.0.user.name', 'Budi Santoso')
            ->assertJsonPath('data.0.user.nim', $resident->nim)
            ->assertJsonPath('data.0.payment_status', 'menunggu')
            ->assertJsonStructure([
                'data' => [[
                    'user' => ['name', 'nim'],
                    'payment_proof_url',
                ]],
            ]);

        $this->assertNotEmpty($response->json('data.0.payment_proof_url'));

        $this->patchJson("/api/admin/bookings/{$booking->id}/payment", [
            'payment_status' => 'ditolak',
        ])
            ->assertOk()
            ->assertJsonPath('data.payment_status', 'ditolak')
            ->assertJsonPath('data.status', 'dibatalkan');
    }

    public function test_admin_can_verify_pending_booking_payment(): void
    {
        $resident = $this->user();
        $admin = $this->user('admin', 'admin@ubbg.ac.id');
        $room = Room::create([
            'code' => 'A-102',
            'floor' => 1,
            'gender' => 'putra',
            'capacity' => 4,
            'deposit' => 500000,
        ]);
        $booking = Booking::create([
            'user_id' => $resident->id,
            'room_id' => $room->id,
            'start_date' => now()->toDateString(),
            'duration_months' => 1,
            'end_date' => now()->addMonth()->toDateString(),
            'occupants' => 1,
            'payment_proof_path' => 'payment-proofs/bukti.png',
        ]);

        Sanctum::actingAs($admin);
        $this->patchJson("/api/admin/bookings/{$booking->id}/payment", [
            'payment_status' => 'terverifikasi',
        ])
            ->assertOk()
            ->assertJsonPath('data.payment_status', 'terverifikasi')
            ->assertJsonPath('data.payment_status_label', 'Terverifikasi')
            ->assertJsonPath('data.status', 'aktif');

        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'payment_status' => 'terverifikasi',
            'status' => 'aktif',
        ]);
    }

    private function user(string $role = 'penghuni', string $email = 'resident@ubbg.ac.id'): User
    {
        return User::create([
            'name' => $role === 'admin' ? 'Admin Rusunawa' : 'Budi Santoso',
            'nim' => $role === 'admin' ? null : '12345678'.substr(md5($email), 0, 4),
            'email' => $email,
            'phone' => '081234567890',
            'password' => 'password-uji',
            'role' => $role,
        ]);
    }
}
