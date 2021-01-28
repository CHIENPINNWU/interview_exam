<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string
     */
    protected function redirectTo($request)
    {
        header('Content-Type: application/json; charset=utf-8');

        $ret = [];
        $ret['success']      = 0;
        $ret['errorCode']    = 7001;
        $ret['errorMessage'] = 'token失效';

        exit(json_encode($ret));

        /*
        if (! $request->expectsJson()) {
            return route('login');
        }
        */
    }
}
