<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class LoginController extends Controller
{
    /** Falhas por e-mail dentro da janela. */
    private const MAX_POR_USUARIO = 5;

    /**
     * Falhas por IP. Acessos pelo Tor costumam chegar com o mesmo IP, então
     * este limite é folgado: o controle principal é por e-mail.
     */
    private const MAX_POR_IP = 30;

    /** Janela, em segundos (15 minutos). */
    private const JANELA = 900;

    private const MENSAGEM_GENERICA = 'Não foi possível entrar com esses dados.';

    private const MENSAGEM_LIMITE = 'Muitas tentativas. Aguarde alguns minutos e tente novamente.';

    public function mostrar()
    {
        return view('auth.login');
    }

    public function entrar(Request $request)
    {
        $email = Str::lower(trim((string) $request->input('email', '')));
        $senha = (string) $request->input('password', '');

        $chaveUsuario = 'login:u:'.sha1($email);
        $chaveIp = 'login:ip:'.sha1((string) $request->ip());

        if (RateLimiter::tooManyAttempts($chaveUsuario, self::MAX_POR_USUARIO)
            || RateLimiter::tooManyAttempts($chaveIp, self::MAX_POR_IP)) {
            return response()->view('auth.login', [
                'erro' => self::MENSAGEM_LIMITE,
                'email' => $email,
            ], 429);
        }

        $entradaPlausivel = $email !== ''
            && $senha !== ''
            && mb_strlen($email) <= 254
            && strlen($senha) <= 1024;

        $autenticado = false;

        if ($entradaPlausivel) {
            if (! User::query()->where('email', $email)->exists()) {
                // Gasta o mesmo tempo de um hash real para não revelar se o e-mail existe.
                Hash::make(Str::random(32));
            }

            $autenticado = Auth::attempt(['email' => $email, 'password' => $senha]);
        }

        if (! $autenticado) {
            RateLimiter::hit($chaveUsuario, self::JANELA);
            RateLimiter::hit($chaveIp, self::JANELA);

            return redirect()
                ->route('login')
                ->withInput(['email' => mb_substr($email, 0, 254)])
                ->with('erro', self::MENSAGEM_GENERICA);
        }

        RateLimiter::clear($chaveUsuario);

        // Evita fixação de sessão.
        $request->session()->regenerate();

        return redirect()->intended(route('inicio'));
    }

    public function sair(Request $request)
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
