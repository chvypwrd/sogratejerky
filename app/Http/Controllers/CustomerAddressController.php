<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCustomerAddressRequest;
use App\Http\Requests\UpdateCustomerAddressRequest;
use App\Models\CustomerAddress;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CustomerAddressController extends Controller
{
    public function index(Request $request): View
    {
        $addresses = $request->user()->addresses()
            ->orderByDesc('is_default_shipping')
            ->orderByDesc('is_default_billing')
            ->latest('updated_at')
            ->get();

        return view('account.addresses.index', compact('addresses'));
    }

    public function create(): View
    {
        return view('account.addresses.create', [
            'address' => new CustomerAddress(['country' => 'US']),
        ]);
    }

    public function store(StoreCustomerAddressRequest $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validated();
        $data['is_default_shipping'] = $request->boolean('is_default_shipping');
        $data['is_default_billing'] = $request->boolean('is_default_billing');

        DB::transaction(function () use ($user, $data) {
            if (! $user->addresses()->exists()) {
                $data['is_default_shipping'] = true;
                $data['is_default_billing'] = true;
            }

            if ($data['is_default_shipping']) {
                $user->addresses()->update(['is_default_shipping' => false]);
            }

            if ($data['is_default_billing']) {
                $user->addresses()->update(['is_default_billing' => false]);
            }

            $user->addresses()->create($data);
        });

        return redirect()
            ->route('account.addresses.index')
            ->with('status', 'Address added successfully.');
    }

    public function edit(Request $request, CustomerAddress $address): View
    {
        $this->authorizeAddress($request, $address);

        return view('account.addresses.edit', compact('address'));
    }

    public function update(UpdateCustomerAddressRequest $request, CustomerAddress $address): RedirectResponse
    {
        $this->authorizeAddress($request, $address);

        $data = $request->validated();
        $data['is_default_shipping'] = $request->boolean('is_default_shipping');
        $data['is_default_billing'] = $request->boolean('is_default_billing');

        DB::transaction(function () use ($request, $address, $data) {
            if ($data['is_default_shipping']) {
                $request->user()->addresses()
                    ->where('id', '!=', $address->id)
                    ->update(['is_default_shipping' => false]);
            }

            if ($data['is_default_billing']) {
                $request->user()->addresses()
                    ->where('id', '!=', $address->id)
                    ->update(['is_default_billing' => false]);
            }

            $address->update($data);
        });

        return redirect()
            ->route('account.addresses.index')
            ->with('status', 'Address updated successfully.');
    }

    public function destroy(Request $request, CustomerAddress $address): RedirectResponse
    {
        $this->authorizeAddress($request, $address);

        DB::transaction(function () use ($request, $address) {
            $wasDefaultShipping = $address->is_default_shipping;
            $wasDefaultBilling = $address->is_default_billing;

            $address->delete();

            if ($wasDefaultShipping || $wasDefaultBilling) {
                $replacement = $request->user()->addresses()->latest('id')->first();

                if ($replacement) {
                    if ($wasDefaultShipping) {
                        $replacement->update(['is_default_shipping' => true]);
                    }

                    if ($wasDefaultBilling) {
                        $replacement->update(['is_default_billing' => true]);
                    }
                }
            }
        });

        return redirect()
            ->route('account.addresses.index')
            ->with('status', 'Address removed successfully.');
    }

    private function authorizeAddress(Request $request, CustomerAddress $address): void
    {
        abort_unless($address->user_id === $request->user()->id, 404);
    }
}
