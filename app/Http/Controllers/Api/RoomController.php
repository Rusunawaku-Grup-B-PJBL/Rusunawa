<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\RoomResource;
use App\Models\Room;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class RoomController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return RoomResource::collection(Room::query()->orderBy('code')->get());
    }

    public function show(Room $room): RoomResource
    {
        $room->load('facilities');

        return new RoomResource($room);
    }
}
