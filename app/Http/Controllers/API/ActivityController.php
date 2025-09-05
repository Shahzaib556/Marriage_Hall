<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ActivityController extends Controller
{
    // helper to log
    public static function log($role, $action, $description = null)
    {
        Activity::create([
            'user_id' => Auth::id(),
            'role' => $role,
            'action' => $action,
            'description' => $description,
        ]);
    }

    // USER activities
    public function myActivities()
    {
        $activities = Activity::where('role', 'user')
            ->where('user_id', Auth::id())
            ->latest()
            ->get();

        return response()->json($activities);
    }

    // OWNER activities
    public function ownerActivities()
    {
        $activities = Activity::where('role', 'owner')
            ->where('user_id', Auth::id())
            ->latest()
            ->get();

        return response()->json($activities);
    }
}
