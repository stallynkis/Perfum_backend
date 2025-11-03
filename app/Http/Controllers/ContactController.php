<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ContactController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'message' => 'required|string|max:1000',
        ]);

        return response()->json([
            'message' => 'Mensaje de contacto recibido exitosamente',
            'data' => [
                'name' => $request->name,
                'email' => $request->email,
                'message' => $request->message,
                'received_at' => now()
            ]
        ]);
    }
}
