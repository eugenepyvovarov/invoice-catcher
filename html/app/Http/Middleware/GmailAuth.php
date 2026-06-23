<?php

namespace App\Http\Middleware;

use App\Services\GmailService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class GmailAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if ($user && GmailService::authCheck($user)) {
            return $next($request);
        }

        // Logged in via session but Gmail token missing/invalid — send to OAuth, not /login
        if ($user) {
            return redirect()->route('gmail.login');
        }

        return redirect()->route('login');
    }
}
