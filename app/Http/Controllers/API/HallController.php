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

    $images = $hall->images ?? []; // keep old images if none uploaded

    if ($request->hasFile('images')) {
        $newImages = [];
        foreach ($request->file('images') as $image) {
            $path = $image->store('halls', 'public');
            $newImages[] = $path;
        }

        // decide: replace or merge
        $images = $newImages; // replace old with new
        // OR: $images = array_merge($images, $newImages); // append new to old
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

    // Delete hall (Owner can delete own, Admin can delete all)
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

    //get all halls for admin with owner info
    public function adminHalls()
    {
        //check is user is admin
        if(Auth::user()->role !== 'admin'){
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        $halls = Hall::with('owner') ->get();
        return response()->json($halls);
    }

    // app/Http/Controllers/API/HallController.php

  public function approvedHalls()
   {
    $halls = Hall::where('status', 'approved')->get();

    return response()->json([
        'status' => true,
        'message' => 'Approved halls fetched successfully',
        'data' => $halls
    ]);
   }
}
