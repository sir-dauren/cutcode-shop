<?php

namespace App\Http\Controllers;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Auth\Events\Registered;
use App\Http\Requests\SignInFormRequest;
use App\Http\Requests\SignUpFormRequest;
use App\Http\Requests\ForgotPasswordFormRequest;
use App\Http\Requests\ResetPasswordFormRequest;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Password;
use Doctrine\DBAL\Driver\IBMDB2\Exception\Factory;
use Illuminate\Console\Application;
use Illuminate\View\View;


class AuthController extends Controller
{
    public function index(): Factory|View|Application
    {
        return view('layouts.auth.index');
    }

    public function signUp(): Factory|View|Application
    {
        return view('layouts.auth.sign-up');
    }

    public function forgot(): Factory|View|Application
    {
        return view('layouts.auth.forgot-password');
    }

    public function signIn(SignInFormRequest $request): RedirectResponse
    {

        if(!auth()->attempt($request->validated())){
            return back()->withErrors([
                'email' => 'The provided credentials do not match our records.',
            ])->onlyInput('email');
        }

        $request->session()->regenerate();
 
        return redirect()
            ->intended(route('home'));
 
    }

    public function store(SignUpFormRequest $request): RedirectResponse
    {

        $user = User::query()->create([
            'name' => $request->get('name'),
            'email' => $request->get('email'),
            'password' => bcrypt($request->get('password')),
        ]);
        
        event(new Registered($user));
        auth()->login($user);
        return redirect()
            ->intended(route('home'));
 
    }

    public function logOut(): RedirectResponse
    {
        auth()->logout();
    
        request()->session()->invalidate();
    
        request()->session()->regenerateToken();
    
        return redirect()
            ->route('home');
    }

    public function forgotPassword(ForgotPasswordFormRequest $request): RedirectResponse
    {
        $request->validate(['email' => 'required|email']);
 
        $status = Password::sendResetLink(
            $request->only('email')
        );
    
        return $status === Password::RESET_LINK_SENT
                    ? back()->with(['message' => __($status)])
                    : back()->withErrors(['email' => __($status)]);
    }

    public function reset(string $token): Factory|View|Application
    {
        return view('layouts.auth.reset-password', [
            'token' => $token]);
    }

    public function resetPassword(ResetPasswordFormRequest $request): RedirectResponse
    {
    
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => bcrypt($password)
                ])->setRememberToken(str()->random(60));
    
                $user->save();
    
                event(new PasswordReset($user));
            }
        );
    
        return $status === Password::PASSWORD_RESET
                    ? redirect()->route('login')->with('message', __($status))
                    : back()->withErrors(['email' => [__($status)]]);
    }
}
