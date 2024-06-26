<?php

namespace App\Http\Middleware;

use Illuminate\Support\Facades\Auth;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApprovedUser
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if(!Auth::check()) {            
            return redirect('/login');
        } else {
            $user = Auth::user();
            if ($user->user_type === '000' || $user->user_type === '009') {
                return redirect('/dashboard');
            }            
        }
        return $next($request);
    }
}
