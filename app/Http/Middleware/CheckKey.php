<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckKey
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        if ($request->headers->get('AT-KEY') == "OWQrOWtkS1ltK3grYTFNV2VZSTRzZz09") {
            return $next($request);
        }

        return response()->json([
            'status' => false,
            'code' => 401,
            'error' => 'Unauthorized'
        ], 401);
    }
}
