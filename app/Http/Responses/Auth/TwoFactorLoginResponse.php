<?php

namespace App\Http\Responses\Auth;

use Laravel\Fortify\Contracts\TwoFactorLoginResponse as TwoFactorLoginResponseContract;

class TwoFactorLoginResponse implements TwoFactorLoginResponseContract
{
    public function toResponse($request)
    {
        return redirect()->intended(route('admin.dashboard'));
    }
}
