<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function store(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();
            
            $user = auth()->user();
            
            // Redirect based on role
            if ($user->isSuperAdmin()) {
                return redirect('/super-admin/dashboard');
            }

            return match($user->role) {
                'Pet Owner' => redirect('/pet-owner/dashboard'),
                'Veterinarian' => redirect('/veterinarian/dashboard'),
                'Staff' => redirect('/staff/dashboard'),
                'Admin' => redirect('/admin/dashboard'),
                default => redirect('/auth/login'),
            };
        }
        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->onlyInput('email');
    }
}

