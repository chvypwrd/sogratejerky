<?php

namespace App\Http\Responses\Auth;

use App\Services\LoginRedirectService;
use Laravel\Fortify\Contracts\PasswordConfirmedResponse as PasswordConfirmedResponseContract;

class PasswordConfirmedResponse implements PasswordConfirmedResponseContract
{
    public function __construct(
        private LoginRedirectService $loginRedirects,
    ) {}

    public function toResponse($request)
    {
        return $this->loginRedirects->response($request);
    }
}
