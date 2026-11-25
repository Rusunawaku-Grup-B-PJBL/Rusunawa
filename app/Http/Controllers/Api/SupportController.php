<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSupportMessageRequest;
use App\Models\SupportConversation;
use App\Models\SupportMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SupportController extends Controller
{
    public function mine(): JsonResponse
    {
        $conversation = SupportConversation::query()
            ->where('user_id', request()->user()->id)
            ->first();
        if (! $conversation) {
            return response()->json([
                'data' => ['conversation_id' => null, 'messages' => []],
            ]);
        }

        $conversation->messages()
            ->whereHas('sender', fn ($query) => $query->where('role', 'admin'))
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json([
            'data' => [
                'conversation_id' => $conversation->id,
                'messages' => $conversation->messages()
                    ->with('sender:id,name,role')
                    ->get()
                    ->map(fn (SupportMessage $message) => $this->messageData($message)),
            ],
        ]);
    }

    public function send(StoreSupportMessageRequest $request): JsonResponse
    {
        $conversation = DB::transaction(function () use ($request): SupportConversation {
            $conversation = SupportConversation::query()->firstOrCreate(
                ['user_id' => $request->user()->id],
                ['status' => 'open'],
            );
            $conversation = SupportConversation::query()
                ->whereKey($conversation->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($conversation->status !== 'open') {
                throw ValidationException::withMessages([
                    'body' => ['Percakapan sudah ditutup. Hubungi pengelola untuk membuka kembali.'],
                ]);
            }

            return $conversation;
        });

        $message = $conversation->messages()->create([
            'sender_id' => $request->user()->id,
            'body' => $request->validated('body'),
        ])->load('sender:id,name,role');

        return response()->json([
            'data' => $this->messageData($message),
        ], 201);
    }

    public function inbox(): JsonResponse
    {
        $conversations = SupportConversation::query()
            ->with(['user:id,name,nim', 'latestMessage.sender:id,name,role'])
            ->withCount([
                'messages as unread_count' => fn ($query) => $query
                    ->whereNull('read_at')
                    ->whereHas('sender', fn ($sender) => $sender->where('role', 'penghuni')),
            ])
            ->orderByDesc('updated_at')
            ->get()
            ->map(fn (SupportConversation $conversation) => [
                'id' => $conversation->id,
                'status' => $conversation->status,
                'user' => [
                    'id' => $conversation->user->id,
                    'name' => $conversation->user->name,
                    'nim' => $conversation->user->nim,
                ],
                'unread_count' => $conversation->unread_count,
                'latest_message' => $conversation->latestMessage
                    ? $this->messageData($conversation->latestMessage)
                    : null,
            ]);

        return response()->json(['data' => $conversations]);
    }

    public function show(SupportConversation $conversation): JsonResponse
    {
        $conversation->messages()
            ->whereHas('sender', fn ($query) => $query->where('role', 'penghuni'))
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        $conversation->load([
            'user:id,name,nim,email',
            'messages.sender:id,name,role',
        ]);

        return response()->json([
            'data' => [
                'id' => $conversation->id,
                'status' => $conversation->status,
                'user' => [
                    'id' => $conversation->user->id,
                    'name' => $conversation->user->name,
                    'nim' => $conversation->user->nim,
                    'email' => $conversation->user->email,
                ],
                'messages' => $conversation->messages
                    ->map(fn (SupportMessage $message) => $this->messageData($message)),
            ],
        ]);
    }

    public function reply(
        StoreSupportMessageRequest $request,
        SupportConversation $conversation,
    ): JsonResponse {
        if ($conversation->status !== 'open') {
            throw ValidationException::withMessages([
                'body' => ['Percakapan sudah ditutup.'],
            ]);
        }

        $message = $conversation->messages()->create([
            'sender_id' => $request->user()->id,
            'body' => $request->validated('body'),
        ])->load('sender:id,name,role');

        return response()->json([
            'data' => $this->messageData($message),
        ], 201);
    }

    private function messageData(SupportMessage $message): array
    {
        return [
            'id' => $message->id,
            'sender_id' => $message->sender_id,
            'sender_name' => $message->sender->name,
            'sender_role' => $message->sender->role,
            'body' => $message->body,
            'created_at' => $message->created_at?->toISOString(),
        ];
    }
}
