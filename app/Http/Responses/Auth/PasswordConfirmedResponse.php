<?php

namespace App\Http\Responses\Auth;

use Laravel\Fortify\Contracts\PasswordConfirmedResponse as PasswordConfirmedResponseContract;

class PasswordConfirmedResponse implements PasswordConfirmedResponseContract
{
    public function toResponse($request)
    {
        return redirect()->intended(route('admin.dashboard'));
    }
}
