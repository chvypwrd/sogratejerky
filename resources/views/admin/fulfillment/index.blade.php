@extends('admin.layouts.app')

@section('title', 'Fulfillment Configuration')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-1">Fulfillment Configuration</h1>
        <p class="text-muted mb-0">Control checkout methods, shipping rules, local delivery zones, and pickup locations.</p>
    </div>
</div>

@if($errors->any())
    <div class="alert alert-danger" role="alert">
        <strong>Please fix the following:</strong>
        <ul class="mb-0 mt-2">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="card mb-4">
    <div class="card-header"><strong>Fulfillment Settings</strong></div>
    <div class="card-body">
        <form action="{{ route('admin.fulfillment.settings.update') }}" method="POST">
            @csrf
            @method('PUT')
            <div class="row g-4">
                <div class="col-lg-5">
                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox" name="shipping_enabled" id="shipping_enabled" value="1" @checked(old('shipping_enabled', $setting->shipping_enabled))>
                        <label class="form-check-label" for="shipping_enabled">Enable shipping</label>
                    </div>
                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox" name="local_delivery_enabled" id="local_delivery_enabled" value="1" @checked(old('local_delivery_enabled', $setting->local_delivery_enabled))>
                        <label class="form-check-label" for="local_delivery_enabled">Enable local delivery</label>
                    </div>
                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox" name="pickup_enabled" id="pickup_enabled" value="1" @checked(old('pickup_enabled', $setting->pickup_enabled))>
                        <label class="form-check-label" for="pickup_enabled">Enable pickup</label>
                    </div>
                </div>
                <div class="col-lg-5">
                    <label for="shipping_basis" class="form-label">Shipping calculation basis</label>
                    <select name="shipping_basis" id="shipping_basis" class="form-select">
                        @foreach($basisOptions as $basis)
                            <option value="{{ $basis->value }}" @selected(old('shipping_basis', $setting->shipping_basis->value) === $basis->value)>{{ $basis->label() }}</option>
                        @endforeach
                    </select>
                    <p class="form-text">Changing the basis is blocked while active shipping rules exist.</p>
                </div>
                <div class="col-lg-2 d-flex align-items-start">
                    <button type="submit" class="btn btn-primary w-100">Save Settings</button>
                </div>

                <div class="col-12">
                    <hr>
                    <h3 class="h6">Local delivery threshold pricing</h3>
                    <p class="text-muted small mb-3">When enabled, eligible delivery subtotals at or below the threshold use the flat fee. Above the threshold, the selected delivery zone fee applies. The threshold does not change ZIP eligibility.</p>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="local_delivery_threshold_enabled" id="local_delivery_threshold_enabled" value="1" @checked(old('local_delivery_threshold_enabled', $setting->local_delivery_threshold_enabled))>
                                <label class="form-check-label" for="local_delivery_threshold_enabled">Enable threshold pricing</label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label for="local_delivery_threshold" class="form-label">Threshold amount ($)</label>
                            <input type="number" min="0" step="0.01" name="local_delivery_threshold" id="local_delivery_threshold" class="form-control @error('local_delivery_threshold') is-invalid @enderror" value="{{ old('local_delivery_threshold', number_format($setting->local_delivery_threshold_cents / 100, 2, '.', '')) }}">
                            @error('local_delivery_threshold')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label for="local_delivery_flat_fee" class="form-label">Flat delivery fee ($)</label>
                            <input type="number" min="0" step="0.01" name="local_delivery_flat_fee" id="local_delivery_flat_fee" class="form-control @error('local_delivery_flat_fee') is-invalid @enderror" value="{{ old('local_delivery_flat_fee', number_format($setting->local_delivery_flat_fee_cents / 100, 2, '.', '')) }}">
                            @error('local_delivery_flat_fee')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="row g-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <strong>Shipping Rate Rules</strong>
                <span class="badge bg-secondary">{{ $setting->shipping_basis->label() }}</span>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.fulfillment.shipping-rules.store') }}" method="POST" class="row g-2 align-items-end mb-4">
                    @csrf
                    <div class="col-md-3">
                        <label class="form-label" for="rule-min">Minimum</label>
                        <input type="number" min="0" step="{{ $setting->shipping_basis === App\Enums\ShippingBasis::Subtotal ? '0.01' : '1' }}" name="min_value" id="rule-min" class="form-control" value="{{ old('min_value') }}" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="rule-max">Maximum</label>
                        <input type="number" min="0" step="{{ $setting->shipping_basis === App\Enums\ShippingBasis::Subtotal ? '0.01' : '1' }}" name="max_value" id="rule-max" class="form-control" value="{{ old('max_value') }}" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label" for="rule-fee">Fee</label>
                        <input type="number" min="0" step="0.01" name="fee" id="rule-fee" class="form-control" value="{{ old('fee') }}" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label" for="rule-sort">Sort</label>
                        <input type="number" name="sort_order" id="rule-sort" class="form-control" value="{{ old('sort_order', 0) }}">
                    </div>
                    <div class="col-md-2">
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" name="is_active" id="rule-active" value="1" @checked(old('is_active', true))>
                            <label class="form-check-label" for="rule-active">Active</label>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Add Rule</button>
                    </div>
                </form>

                @if($shippingRules->isEmpty())
                    <p class="text-muted mb-0">No shipping rules yet. Checkout will reject shipping until an active range matches.</p>
                @else
                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead>
                                <tr>
                                    <th>Range</th>
                                    <th>Fee</th>
                                    <th>Status</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($shippingRules as $rule)
                                    <tr>
                                        <td>{{ $setting->shipping_basis === App\Enums\ShippingBasis::Subtotal ? '$'.number_format($rule->min_value / 100, 2).' – $'.number_format($rule->max_value / 100, 2) : $rule->min_value.' – '.$rule->max_value.' items' }}</td>
                                        <td>${{ number_format($rule->fee_cents / 100, 2) }}</td>
                                        <td><span class="badge bg-{{ $rule->is_active ? 'success' : 'secondary' }}">{{ $rule->is_active ? 'Active' : 'Inactive' }}</span></td>
                                        <td class="text-end">
                                            <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#rule-edit-{{ $rule->id }}">Edit</button>
                                            <form action="{{ route('admin.fulfillment.shipping-rules.destroy', $rule) }}" method="POST" class="d-inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this shipping rule?')">Delete</button>
                                            </form>
                                        </td>
                                    </tr>
                                    <tr class="collapse" id="rule-edit-{{ $rule->id }}">
                                        <td colspan="4">
                                            <form action="{{ route('admin.fulfillment.shipping-rules.update', $rule) }}" method="POST" class="row g-2 align-items-end p-3 bg-light rounded">
                                                @csrf
                                                @method('PUT')
                                                <div class="col-md-2">
                                                    <label class="form-label">Minimum</label>
                                                    <input type="number" min="0" step="{{ $setting->shipping_basis === App\Enums\ShippingBasis::Subtotal ? '0.01' : '1' }}" name="min_value" class="form-control" value="{{ $setting->shipping_basis === App\Enums\ShippingBasis::Subtotal ? number_format($rule->min_value / 100, 2, '.', '') : $rule->min_value }}" required>
                                                </div>
                                                <div class="col-md-2">
                                                    <label class="form-label">Maximum</label>
                                                    <input type="number" min="0" step="{{ $setting->shipping_basis === App\Enums\ShippingBasis::Subtotal ? '0.01' : '1' }}" name="max_value" class="form-control" value="{{ $setting->shipping_basis === App\Enums\ShippingBasis::Subtotal ? number_format($rule->max_value / 100, 2, '.', '') : $rule->max_value }}" required>
                                                </div>
                                                <div class="col-md-2">
                                                    <label class="form-label">Fee</label>
                                                    <input type="number" min="0" step="0.01" name="fee" class="form-control" value="{{ number_format($rule->fee_cents / 100, 2, '.', '') }}" required>
                                                </div>
                                                <div class="col-md-2">
                                                    <label class="form-label">Sort</label>
                                                    <input type="number" name="sort_order" class="form-control" value="{{ $rule->sort_order }}">
                                                </div>
                                                <div class="col-md-2">
                                                    <div class="form-check mb-2">
                                                        <input class="form-check-input" type="checkbox" name="is_active" value="1" @checked($rule->is_active)>
                                                        <label class="form-check-label">Active</label>
                                                    </div>
                                                </div>
                                                <div class="col-md-2">
                                                    <button type="submit" class="btn btn-primary w-100">Update</button>
                                                </div>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header"><strong>Local Delivery Zones</strong></div>
            <div class="card-body">
                <form action="{{ route('admin.fulfillment.delivery-zones.store') }}" method="POST" class="row g-2 align-items-end mb-4">
                    @csrf
                    <div class="col-sm-5">
                        <label class="form-label" for="zone-name">Name</label>
                        <input type="text" name="name" id="zone-name" class="form-control" value="{{ old('name') }}">
                    </div>
                    <div class="col-sm-4">
                        <label class="form-label" for="zone-zip">ZIP Code</label>
                        <input type="text" name="zip_code" id="zone-zip" class="form-control" value="{{ old('zip_code') }}" required>
                    </div>
                    <div class="col-sm-3">
                        <label class="form-label" for="zone-fee">Fee</label>
                        <input type="number" min="0" step="0.01" name="fee" id="zone-fee" class="form-control" value="{{ old('fee') }}" required>
                    </div>
                    <div class="col-sm-6">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="is_active" id="zone-active" value="1" @checked(old('is_active', true))>
                            <label class="form-check-label" for="zone-active">Active</label>
                        </div>
                    </div>
                    <div class="col-sm-6 text-end">
                        <button type="submit" class="btn btn-primary">Add Zone</button>
                    </div>
                </form>

                @if($deliveryZones->isEmpty())
                    <p class="text-muted mb-0">No delivery zones yet.</p>
                @else
                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead><tr><th>ZIP</th><th>Fee</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
                            <tbody>
                                @foreach($deliveryZones as $zone)
                                    <tr>
                                        <td>{{ $zone->zip_code }}<br><small class="text-muted">{{ $zone->name }}</small></td>
                                        <td>${{ number_format($zone->fee_cents / 100, 2) }}</td>
                                        <td><span class="badge bg-{{ $zone->is_active ? 'success' : 'secondary' }}">{{ $zone->is_active ? 'Active' : 'Inactive' }}</span></td>
                                        <td class="text-end">
                                            <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#zone-edit-{{ $zone->id }}">Edit</button>
                                            <form action="{{ route('admin.fulfillment.delivery-zones.destroy', $zone) }}" method="POST" class="d-inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this delivery zone?')">Delete</button>
                                            </form>
                                        </td>
                                    </tr>
                                    <tr class="collapse" id="zone-edit-{{ $zone->id }}">
                                        <td colspan="4">
                                            <form action="{{ route('admin.fulfillment.delivery-zones.update', $zone) }}" method="POST" class="row g-2 align-items-end p-3 bg-light rounded">
                                                @csrf
                                                @method('PUT')
                                                <div class="col-sm-4"><label class="form-label">Name</label><input type="text" name="name" class="form-control" value="{{ $zone->name }}"></div>
                                                <div class="col-sm-3"><label class="form-label">ZIP</label><input type="text" name="zip_code" class="form-control" value="{{ $zone->zip_code }}" required></div>
                                                <div class="col-sm-2"><label class="form-label">Fee</label><input type="number" min="0" step="0.01" name="fee" class="form-control" value="{{ number_format($zone->fee_cents / 100, 2, '.', '') }}" required></div>
                                                <div class="col-sm-2"><div class="form-check"><input class="form-check-input" type="checkbox" name="is_active" value="1" @checked($zone->is_active)><label class="form-check-label">Active</label></div></div>
                                                <div class="col-sm-1"><button type="submit" class="btn btn-primary w-100">Save</button></div>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header"><strong>Pickup Locations</strong></div>
            <div class="card-body">
                <form action="{{ route('admin.fulfillment.pickup-locations.store') }}" method="POST" class="row g-2 align-items-end mb-4">
                    @csrf
                    <div class="col-sm-6"><label class="form-label" for="pickup-name">Name</label><input type="text" name="name" id="pickup-name" class="form-control" value="{{ old('name') }}" required></div>
                    <div class="col-sm-6"><label class="form-label" for="pickup-fee">Fee</label><input type="number" min="0" step="0.01" name="fee" id="pickup-fee" class="form-control" value="{{ old('fee') }}" required></div>
                    <div class="col-12"><label class="form-label" for="pickup-address">Address</label><textarea name="address" id="pickup-address" class="form-control" rows="2" required>{{ old('address') }}</textarea></div>
                    <div class="col-12"><label class="form-label" for="pickup-contact">Contact Details</label><input type="text" name="contact_details" id="pickup-contact" class="form-control" value="{{ old('contact_details') }}"></div>
                    <div class="col-12"><label class="form-label" for="pickup-instructions">Instructions</label><textarea name="instructions" id="pickup-instructions" class="form-control" rows="2">{{ old('instructions') }}</textarea></div>
                    <div class="col-sm-6"><div class="form-check"><input class="form-check-input" type="checkbox" name="is_active" id="pickup-active" value="1" @checked(old('is_active', true))><label class="form-check-label" for="pickup-active">Active</label></div></div>
                    <div class="col-sm-6 text-end"><button type="submit" class="btn btn-primary">Add Pickup Location</button></div>
                </form>

                @if($pickupLocations->isEmpty())
                    <p class="text-muted mb-0">No pickup locations yet.</p>
                @else
                    @foreach($pickupLocations as $location)
                        <div class="border rounded p-3 mb-3">
                            <div class="d-flex justify-content-between gap-3">
                                <div>
                                    <strong>{{ $location->name }}</strong>
                                    <div class="small text-muted">{{ $location->address }}</div>
                                    <div class="small">Fee: ${{ number_format($location->fee_cents / 100, 2) }}</div>
                                    <span class="badge bg-{{ $location->is_active ? 'success' : 'secondary' }}">{{ $location->is_active ? 'Active' : 'Inactive' }}</span>
                                </div>
                                <div class="text-end">
                                    <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#pickup-edit-{{ $location->id }}">Edit</button>
                                    <form action="{{ route('admin.fulfillment.pickup-locations.destroy', $location) }}" method="POST" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this pickup location?')">Delete</button>
                                    </form>
                                </div>
                            </div>
                            <div class="collapse mt-3" id="pickup-edit-{{ $location->id }}">
                                <form action="{{ route('admin.fulfillment.pickup-locations.update', $location) }}" method="POST" class="row g-2">
                                    @csrf
                                    @method('PUT')
                                    <div class="col-sm-8"><label class="form-label">Name</label><input type="text" name="name" class="form-control" value="{{ $location->name }}" required></div>
                                    <div class="col-sm-4"><label class="form-label">Fee</label><input type="number" min="0" step="0.01" name="fee" class="form-control" value="{{ number_format($location->fee_cents / 100, 2, '.', '') }}" required></div>
                                    <div class="col-12"><label class="form-label">Address</label><textarea name="address" class="form-control" rows="2" required>{{ $location->address }}</textarea></div>
                                    <div class="col-sm-6"><label class="form-label">Contact Details</label><input type="text" name="contact_details" class="form-control" value="{{ $location->contact_details }}"></div>
                                    <div class="col-sm-6"><label class="form-label">Instructions</label><input type="text" name="instructions" class="form-control" value="{{ $location->instructions }}"></div>
                                    <div class="col-sm-6"><div class="form-check"><input class="form-check-input" type="checkbox" name="is_active" value="1" @checked($location->is_active)><label class="form-check-label">Active</label></div></div>
                                    <div class="col-sm-6 text-end"><button type="submit" class="btn btn-primary">Save Changes</button></div>
                                </form>
                            </div>
                        </div>
                    @endforeach
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
