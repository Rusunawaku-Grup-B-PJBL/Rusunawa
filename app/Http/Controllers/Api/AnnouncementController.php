<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AnnouncementController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $readIds = $request->user()->readAnnouncements()
            ->pluck('announcements.id')
            ->map(fn ($id) => (int) $id)
            ->all();
        $announcements = Announcement::query()
            ->where('published_at', '<=', now())
            ->orderByDesc('published_at')
            ->get()
            ->map(fn (Announcement $announcement) => [
                'id' => $announcement->id,
                'title' => $announcement->title,
                'body' => $announcement->body,
                'published_at' => $announcement->published_at?->toISOString(),
                'is_read' => in_array($announcement->id, $readIds, true),
            ]);

        return response()->json(['data' => $announcements]);
    }

    public function markRead(Request $request, Announcement $announcement): JsonResponse
    {
        abort_if($announcement->published_at?->isFuture(), 404);

        $request->user()->readAnnouncements()->syncWithoutDetaching([
            $announcement->id => ['read_at' => now()],
        ]);

        return response()->json([
            'message' => 'Pengumuman ditandai sudah dibaca.',
            'data' => ['id' => $announcement->id, 'is_read' => true],
        ]);
    }
}
