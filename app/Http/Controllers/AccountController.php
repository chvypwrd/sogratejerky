<?php

namespace App\Http\Controllers;

use App\Services\CartService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AccountController extends Controller
{
    public function dashboard(Request $request, CartService $cartService): View
    {
        $user = $request->user();

        return view('account.dashboard', [
            'user' => $user,
            'addressCount' => $user->addresses()->count(),
            'cartItemCount' => $cartService->existingItemCount(),
        ]);
    }
}
