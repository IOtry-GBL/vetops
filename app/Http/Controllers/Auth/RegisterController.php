<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Rules\StrongPassword;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class RegisterController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'full_name' => 'required|string|max:100',
            'email' => 'required|email|unique:users,email',
            'contact_no' => 'required|string|max:20',
            'password' => ['required', new StrongPassword(), 'confirmed'],
        ]);

        $user = User::create([
            'full_name' => $validated['full_name'],
            'email' => $validated['email'],
            'contact_no' => $validated['contact_no'],
            'password' => Hash::make($validated['password']),
            'role' => 'Pet Owner',
            'status' => 'Active',
        ]);

        Auth::login($user);

        return redirect('/pet-owner/dashboard');
    }
}
