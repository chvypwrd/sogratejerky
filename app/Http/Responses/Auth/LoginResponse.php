<?php

namespace App\Http\Responses\Auth;

use App\Services\LoginRedirectService;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;

class LoginResponse implements LoginResponseContract
{
    public function __construct(
        private LoginRedirectService $loginRedirects,
    ) {}

    public function toResponse($request)
    {
        return $this->loginRedirects->response($request);
    }
}
