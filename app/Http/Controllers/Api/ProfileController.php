<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TilawaResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    /** GET /api/library — saved tilawat for the authenticated user */
    public function library(Request $request)
    {
        $saved = $request->user()
            ->savedTilawat()
            ->with('tilawa.qari')
            ->latest()
            ->paginate(20);

        $tilawat = $saved->through(fn ($s) => $s->tilawa);

        return TilawaResource::collection($tilawat);
    }

    /** PUT /api/profile */
    public function update(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'name'         => 'sometimes|required|string|max:255',
            'password'     => 'sometimes|required|min:8|confirmed',
            'avatar'       => 'sometimes|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        if ($request->filled('name')) {
            $user->name = $request->name;
        }

        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }

        if ($request->hasFile('avatar')) {
            $user->avatar = $request->file('avatar')->store('avatars', 'public');
        }

        $user->save();

        return response()->json([
            'user' => [
                'id'         => $user->id,
                'name'       => $user->name,
                'email'      => $user->email,
                'avatar_url' => $user->avatar_url,
                'roles'      => $user->getRoleNames(),
            ],
        ]);
    }
}
