<?php

namespace App\Http\Middleware;

use Closure;
use App\UserTokens;

class AuthLoginMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $token = $request->bearerToken();
        
        if ($token) {
            $check =  UserTokens::where('token', $token)->first();

            if (!$check) {
                return response()->json(['statusCode' => '601','message' => 'Token tidak sesuai']);
            } else {
                return $next($request);
            }
        } else {
            return response()->json(['statusCode' => '401','message' => 'Silahkan Masukkan Token.']);
        }
    }
}
