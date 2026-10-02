<?php

namespace App\Http\Controllers;

use App\Models\ChatConversation;
use App\Models\User;
use App\Support\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showRegister(): View
    {
        return view('auth.register');
    }

    public function register(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:30', 'unique:users,phone'],
            'email' => ['nullable', 'email', 'max:255', 'unique:users,email'],
            'address' => ['required', 'string', 'max:255'],
            'block' => ['required', 'string', 'max:20'],
            'house_number' => ['required', 'string', 'max:20'],
            'password' => ['required', 'confirmed', 'min:8'],
        ]);

        $user = User::create($validated);
        AuditLogger::log('USER_REGISTERED', 'User', $user->id, ['role' => $user->role]);

        auth()->login($user);

        return redirect()->route('dashboard');
    }

    public function showLogin(): View
    {
        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $field = filter_var($credentials['login'], FILTER_VALIDATE_EMAIL) ? 'email' : 'phone';

        if (! auth()->attempt([$field => $credentials['login'], 'password' => $credentials['password'], 'status' => 'active'])) {
            return back()->withErrors(['login' => 'Nomor HP/email atau password tidak sesuai.'])->onlyInput('login');
        }

        $request->session()->regenerate();
        AuditLogger::log('USER_LOGIN', 'User', auth()->id(), ['role' => auth()->user()->role]);

        return redirect()->route('dashboard');
    }

    public function dashboard(): View
    {
        $user = auth()->user();
        $store = $user->isSeller() ? $user->sellerProfile()->first() : null;

        return view('dashboard', [
            'user' => $user,
            'store' => $store,
            'unreadNotifications' => $user->userNotifications()->unread()->count(),
            'unreadChats' => ChatConversation::unreadConversationCountFor($user),
        ]);
    }

    public function logout(Request $request): RedirectResponse
    {
        auth()->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
