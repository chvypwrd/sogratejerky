<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class SecurityController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        return view('admin.security.index', [
            'user' => $user,
            'enabled' => $user->two_factor_secret !== null,
        ]);
    }
}
