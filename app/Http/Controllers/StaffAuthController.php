<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StaffAuthController extends Controller
{
    public function showLogin()
    {
        // Already logged in? Go straight to the dashboard.
        if (Auth::check()) {
            return redirect()->route('staff.dashboard');
        }

        return view('login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        // No "remember me" — the login ends when the browser closes
        if (Auth::attempt($credentials)) {
            // Start a fresh session after logging in (security best practice)
            $request->session()->regenerate();

            return redirect()->intended(route('staff.dashboard'))->with('just_logged_in', true);
        }

        return back()
            ->withInput($request->only('username'))
            ->withErrors(['username' => 'Wrong username or password.']);
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}