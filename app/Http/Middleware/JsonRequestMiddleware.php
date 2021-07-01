<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class JsonRequestMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->isJson() && count($request->json()) != 0) {
            return $next($request);
        }
        else {
            return response()->json(['statusCode' => '602','message' => 'Hanya JSON']);
        }
    }
}