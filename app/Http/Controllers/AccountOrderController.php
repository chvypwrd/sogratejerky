<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AccountOrderController extends Controller
{
    public function index(Request $request): View
    {
        return view('account.orders.index', [
            'orders' => $request->user()
                ->orders()
                ->withCount('items')
                ->latest('placed_at')
                ->paginate(10),
        ]);
    }

    public function show(Request $request, Order $order): View
    {
        abort_unless($order->user_id === $request->user()->id, 404);

        return view('account.orders.show', [
            'order' => $order->load('items'),
        ]);
    }
}
