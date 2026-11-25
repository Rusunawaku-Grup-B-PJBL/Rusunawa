<?php

use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\AnnouncementController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\ComplaintTicketController;
use App\Http\Controllers\Api\DamageReportController;
use App\Http\Controllers\Api\HandoverController;
use App\Http\Controllers\Api\RoomController;
use App\Http\Controllers\Api\SupportController;
use Illuminate\Support\Facades\Route;

// Publik
Route::middleware('throttle:20,1')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
});

// Terautentikasi (Sanctum)
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);

    Route::get('/rooms', [RoomController::class, 'index']);
    Route::get('/rooms/{room}', [RoomController::class, 'show']);
    Route::get('/announcements', [AnnouncementController::class, 'index']);
    Route::post('/announcements/{announcement}/read', [AnnouncementController::class, 'markRead']);

    Route::get('/support/messages', [SupportController::class, 'mine']);
    Route::post('/support/messages', [SupportController::class, 'send']);
    Route::get('/complaints/mine', [ComplaintTicketController::class, 'mine']);
    Route::post('/complaints', [ComplaintTicketController::class, 'store']);

    Route::get('/bookings/mine', [BookingController::class, 'mine']);
    Route::post('/bookings', [BookingController::class, 'store']);

    Route::get('/damage-reports/mine', [DamageReportController::class, 'mine']);
    Route::post('/damage-reports', [DamageReportController::class, 'store']);

    Route::get('/handovers/mine', [HandoverController::class, 'mine']);
    Route::post('/handovers', [HandoverController::class, 'store']);

    Route::middleware('role:admin')->prefix('admin')->group(function () {
        Route::get('/bookings', [AdminController::class, 'bookings']);
        Route::get('/damage-reports', [AdminController::class, 'damageReports']);
        Route::patch('/bookings/{booking}/payment', [AdminController::class, 'updatePayment']);
        Route::patch('/damage-reports/{damageReport}', [AdminController::class, 'updateDamageReport']);
        Route::post('/announcements', [AdminController::class, 'storeAnnouncement']);
        Route::get('/support/conversations', [SupportController::class, 'inbox']);
        Route::get('/support/conversations/{conversation}', [SupportController::class, 'show']);
        Route::post('/support/conversations/{conversation}/messages', [SupportController::class, 'reply']);
        Route::get('/complaints', [AdminController::class, 'complaintTickets']);
        Route::patch('/complaints/{complaintTicket}', [AdminController::class, 'updateComplaintTicket']);
    });
});
