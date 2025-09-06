<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Hall;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class HallController extends Controller
{
    // Owner adds new hall
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'location' => 'required|string|max:255',
            'capacity' => 'required|integer',
            'pricing' => 'required|numeric',
            'facilities' => 'nullable|array',
            'facilities.*' => 'nullable|string|max:255',
            'images.*' => 'nullable|image|mimes:jpg,jpeg,png|max:2048'
        ]);

        $images = [];
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $path = $image->store('halls', 'public');
                $images[] = $path;
            }
        }

        $hall = Hall::create([
            'owner_id' => Auth::id(),
            'name' => $request->name,
            'location' => $request->location,
            'capacity' => $request->capacity,
            'pricing' => $request->pricing,
            'facilities' => $request->facilities,
            'images' => $images,
        ]);

        return response()->json(['message' => 'Hall submitted for approval', 'hall' => $hall], 201);
    }

    // Owner updates hall
    public function update(Request $request, $id)
    {
        \Log::debug('Request data:', $request->all());
        \Log::debug('Files:', $request->file() ?: []);
        \Log::debug('Content-Type:', [$request->header('Content-Type')]);

        $hall = Hall::findOrFail($id);

        if ($hall->owner_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'location' => 'required|string|max:255',
            'capacity' => 'required|integer',
            'pricing' => 'required|numeric',
            'facilities' => 'nullable|array',
            'facilities.*' => 'nullable|string|max:255',
            'images.*' => 'nullable|image|mimes:jpg,jpeg,png|max:2048'
        ]);

        $images = $hall->images ?? [];

        if ($request->hasFile('images')) {
            $newImages = [];
            foreach ($request->file('images') as $image) {
                $path = $image->store('halls', 'public');
                $newImages[] = $path;
            }
            $images = $newImages; // Replace old with new
        }

        $hall->update([
            'name' => $request->name,
            'location' => $request->location,
            'capacity' => $request->capacity,
            'pricing' => $request->pricing,
            'facilities' => $request->facilities,
            'images' => $images,
        ]);

        return response()->json([
            'message' => 'Hall updated successfully',
            'hall' => $hall
        ]);
    }

    // Owner halls list
    public function myHalls()
    {
        $halls = Hall::where('owner_id', Auth::id())->get();
        return response()->json($halls);
    }

    // Admin approves hall
    public function approve($id)
    {
        $hall = Hall::findOrFail($id);
        $hall->status = 'approved';
        $hall->save();

        return response()->json(['message' => 'Hall approved successfully']);
    }

    // Admin deactivates hall
    public function deactivate($id)
    {
        $hall = Hall::findOrFail($id);
        $hall->status = 'inactive';
        $hall->save();

        return response()->json(['message' => 'Hall deactivated successfully']);
    }

    // Delete hall
    public function destroy($id)
    {
        $hall = Hall::findOrFail($id);

        if (Auth::user()->role !== 'admin' && $hall->owner_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $hall->delete();
        return response()->json(['message' => 'Hall deleted successfully']);
    }

    // Public list of approved halls
    public function index()
    {
        $halls = Hall::where('status', 'approved')->get();
        return response()->json($halls);
    }

    // Admin view all halls with owner info
    public function adminHalls()
    {
        if (Auth::user()->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        $halls = Hall::with('owner')->get();
        return response()->json($halls);
    }

    // Approved halls only
    public function approvedHalls()
    {
        $halls = Hall::where('status', 'approved')->get();

        return response()->json([
            'status' => true,
            'message' => 'Approved halls fetched successfully',
            'data' => $halls
        ]);
    }

    // ✅ New: Halls that have been booked at least once
    public function previouslyBookedHalls()
    {
        $halls = Hall::whereHas('bookings') // only halls with bookings
                     ->withCount('bookings')
                     ->with('owner:id,name,email')
                     ->get();

        return response()->json([
            'status' => true,
            'message' => 'Previously booked halls fetched successfully',
            'data' => $halls
        ]);
    }
}
