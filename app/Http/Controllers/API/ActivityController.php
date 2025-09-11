<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class ActivityController extends Controller
{
    // helper to log activity (auto-delete old > 48hrs)
    public static function log($role, $name, $action, $description = null, $hallName = null)
    {
        // Delete all activities older than 48 hours for this user
        Activity::where('user_id', Auth::id())
            ->where('created_at', '<', Carbon::now()->subHours(48))
            ->delete();

        // Insert new activity
        Activity::create([
            'user_id' => Auth::id(),
            'role' => $role,
            'name' => $name,       // user name
            'hall_name' => $hallName,   // new hall name field
            'action' => $action,
            'description' => $description,
        ]);
    }

    // USER activities
    public function myActivities()
    {
        $activities = Activity::where('role', 'user')
            ->where('user_id', Auth::id())
            ->where('created_at', '>=', now()->subHours(48)) // fetch only last 48 hrs
            ->latest()
            ->get();

        return response()->json($activities);
    }

    // OWNER activities
    public function ownerActivities()
    {
        $activities = Activity::where('role', 'owner')
            ->where('user_id', Auth::id())
            ->where('created_at', '>=', now()->subHours(48)) // fetch only last 48 hrs
            ->latest()
            ->get();

        return response()->json($activities);
    }
}
