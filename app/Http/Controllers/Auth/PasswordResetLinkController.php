<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

class PasswordResetLinkController extends Controller
{
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    /**
     * Always reports success so the form cannot be used to discover registered emails.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate(['email' => ['required', 'email']]);

        try {
            Password::sendResetLink($request->only('email'));
        } catch (TransportExceptionInterface $exception) {
            report($exception);
        }

        return back()->with('status', 'If that email is registered, we have sent a password reset link.');
    }
}
