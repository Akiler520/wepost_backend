<?php

namespace App\Http\Middleware;

use App\Models\Log;
use App\Models\User;
use Closure;
use App\Lib\MTResponse;

class PermissionMiddleware
{
    private $_whiteList = [
        '/user/login',
        '/user/client',
        '/article/list',
        '/article/share',
        '/user/message',
    ];
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string|null  $guard
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $uri = $request->getRequestUri();
        // check token
        $token = $request->input("token");
        $userInfo = null;

        if ($token) {
            $userObj = new  User();

            $userInfo = $userObj->loginCheck($token);
        }


        if (!in_array($uri, $this->_whiteList)) {
            if (!$token) {
                MTResponse::jsonResponse("对不起，您没有登录", RESPONSE_NO_LOGIN);
            }

            if (!$userInfo) {
                MTResponse::jsonResponse("对不起，您没有登录", RESPONSE_NO_LOGIN);
            }

        }

        // save global user info
        $_SERVER['userInfo'] = $userInfo;

        $requestData = json_encode($request->all());

        !in_array($uri, $this->_whiteList) && Log::saveLog($uri, $requestData);

        return $next($request);
    }
}
