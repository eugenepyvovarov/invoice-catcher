<?php

namespace App\Http\Middleware;

use Closure;
use Dacastro4\LaravelGmail\Facade\LaravelGmail;
use Illuminate\Http\Request;

class GmailAuth
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if (LaravelGmail::check()) {
            return $next($request);
        }
        auth()->logout();
        return redirect()->route('home');

    }
}
