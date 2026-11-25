<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreComplaintTicketRequest;
use App\Http\Resources\ComplaintTicketResource;
use App\Models\ComplaintTicket;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class ComplaintTicketController extends Controller
{
    public function mine(): JsonResponse
    {
        $tickets = request()->user()->complaintTickets()
            ->with('logs')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (ComplaintTicket $ticket) => (new ComplaintTicketResource($ticket))->resolve());

        return response()->json(['data' => $tickets]);
    }

    public function store(StoreComplaintTicketRequest $request): JsonResponse
    {
        $ticket = DB::transaction(function () use ($request): ComplaintTicket {
            $ticket = $request->user()->complaintTickets()->create([
                ...$request->validated(),
                'status' => 'dikirim',
            ]);
            $ticket->logs()->create([
                'author_id' => $request->user()->id,
                'status' => 'dikirim',
                'note' => 'Keluhan diterima.',
            ]);

            return $ticket->load('logs');
        });

        return response()->json([
            'data' => (new ComplaintTicketResource($ticket))->resolve(),
        ], 201);
    }
}
