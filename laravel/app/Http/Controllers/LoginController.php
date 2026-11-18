<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class LoginController extends Controller
{
    public function formulario()
    {
        return Inertia::render('Auth/Login');
    }

    public function entrar(Request $r)
    {
        $cred = $r->validate(['email' => 'required|email', 'password' => 'required']);
        if (! Auth::attempt($cred, true)) {
            return back()->withErrors(['email' => 'Credenciais invalidas.']);
        }
        $r->session()->regenerate();

        return redirect()->intended('/');
    }

    public function sair(Request $r)
    {
        Auth::logout();
        $r->session()->invalidate();
        $r->session()->regenerateToken();

        return redirect()->route('login');
    }
}
