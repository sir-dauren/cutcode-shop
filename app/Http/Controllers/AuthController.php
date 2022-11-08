<?php

namespace App\Http\Controllers;
use App\Support\Flash\Flash;
use App\Models\User;
use Illuminate\View\View;
use Illuminate\Console\Application;
use Illuminate\Http\RedirectResponse;
use Illuminate\Auth\Events\Registered;
use App\Http\Requests\SignInFormRequest;
use App\Http\Requests\SignUpFormRequest;
use Illuminate\Support\Facades\Password;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Auth\Events\PasswordReset;
use App\Http\Requests\ResetPasswordFormRequest;
use App\Http\Requests\ForgotPasswordFormRequest;
use Doctrine\DBAL\Driver\IBMDB2\Exception\Factory;

class AuthController extends Controller
{
    public function index(): Factory|View|Application|RedirectResponse
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
        
        if($status === Password::RESET_LINK_SENT){
            flash()->info(__($status));

            return back();
        }
        
        return back()->withErrors(['email' => __($status)]);
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

        if($status === Password::PASSWORD_RESET){
            flash()->info(__($status));

            return back();
        }
        
        return back()->withErrors(['email' => __($status)]);
    
        return redirect()->route('login')->with('message', __($status));
    }

    public function github(): RedirectResponse
    {

        return Socialite::driver('github')->redirect();
    }

    public function  githubCallback(){

        $githubUser = Socialite::driver('github')->user();
 
        $user = User::query()->updateOrCreate([
            'github_id' => $githubUser->id,
        ], [
            'name' => $githubUser->name,
            'email' => $githubUser->email,
            'password' => bcrypt(str()->random(20)),
        ]);
    
        auth()->login($user);
    
        return redirect()->intended(route('home'));
    }   
}
