<?php
// app/Http/Controllers/API/ContactUsController.php
namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ContactUs;

class ContactUsController extends Controller
{
    // Store message from user
    public function store(Request $request)
    {
        $data = $request->validate([
            'name'    => 'required|string|max:255',
            'email'   => 'required|email',
            'phone'   => 'nullable|string|max:20',
            'message' => 'required|string',
        ]);

        $contact = ContactUs::create($data);

        return response()->json([
            'message' => 'Your message has been sent successfully!',
            'data' => $contact,
        ], 201);
    }

    // Admin: View all messages
    public function index()
    {
        $messages = ContactUs::latest()->get();
        return response()->json($messages);
    }

    // Admin: Delete a message
    public function destroy($id)
    {
        $contact = ContactUs::findOrFail($id);
        $contact->delete();

        return response()->json(['message' => 'Message deleted successfully']);
    }
}
