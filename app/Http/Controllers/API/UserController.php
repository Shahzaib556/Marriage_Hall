<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    // List all users (admin only)
    public function index()
    {
        return response()->json(
            User::where('role', '!=', 'admin') // exclude admins
                ->orderBy('created_at', 'desc')
                ->paginate(20)
        );
    }

    // Show one user
    public function show($id)
    {
        return response()->json(User::findOrFail($id));
    }

    // Update role/status
    public function update(Request $request, $id)
    {
        $data = $request->validate([
            'role' => 'nullable|in:user,owner,admin',
            'is_active' => 'nullable|boolean',
            'name' => 'nullable|string|max:255',
        ]);

        $user = User::findOrFail($id);

        if (isset($data['role'])) {
            $user->role = $data['role'];
        }
        if (isset($data['is_active'])) {
            $user->is_active = $data['is_active'];
        }
        if (isset($data['name'])) {
            $user->name = $data['name'];
        }

        $user->save();

        return response()->json(['message' => 'User updated', 'user' => $user]);
    }

    // Delete
    public function destroy($id)
    {
        User::findOrFail($id)->delete();

        return response()->json(['message' => 'User deleted']);
    }
}
