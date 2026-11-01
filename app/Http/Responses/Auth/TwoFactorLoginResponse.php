<?php

namespace App\Http\Responses\Auth;

use App\Services\LoginRedirectService;
use Laravel\Fortify\Contracts\TwoFactorLoginResponse as TwoFactorLoginResponseContract;

class TwoFactorLoginResponse implements TwoFactorLoginResponseContract
{
    public function __construct(
        private LoginRedirectService $loginRedirects,
    ) {}

    public function toResponse($request)
    {
        return $this->loginRedirects->response($request);
    }
}
