<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Services\JwtService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class AuthenticateJwt
{
    public function __construct(private readonly JwtService $jwt)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        try {
            $payload = $this->jwt->decode((string) $request->cookie(config('jwt.cookie')));
            $user = User::query()->findOrFail($payload['sub']);

            if (! $user->isAdmin() || $user->jwt_version !== (int) $payload['ver']) {
                return $this->unauthenticated($request);
            }

            Auth::setUser($user);
            $request->setUserResolver(fn () => $user);

            return $next($request);
        } catch (Throwable) {
            return $this->unauthenticated($request);
        }
    }

    private function unauthenticated(Request $request): Response
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        return redirect()->guest(route('admin.login'))->withCookie($this->jwt->forgetCookie());
    }
}
