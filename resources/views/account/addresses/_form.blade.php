@if($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="card">
    <div class="card-body p-4">
        <div class="row g-3">
            <div class="col-md-6">
                <label for="label" class="form-label">Label</label>
                <input type="text" class="form-control @error('label') is-invalid @enderror" id="label" name="label" value="{{ old('label', $address->label) }}" maxlength="100" placeholder="Home, Work, etc.">
                @error('label')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6">
                <label for="recipient_name" class="form-label">Recipient Name</label>
                <input type="text" class="form-control @error('recipient_name') is-invalid @enderror" id="recipient_name" name="recipient_name" value="{{ old('recipient_name', $address->recipient_name) }}" required maxlength="255">
                @error('recipient_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6">
                <label for="company" class="form-label">Company</label>
                <input type="text" class="form-control @error('company') is-invalid @enderror" id="company" name="company" value="{{ old('company', $address->company) }}" maxlength="255">
                @error('company')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6">
                <label for="phone" class="form-label">Phone</label>
                <input type="text" class="form-control @error('phone') is-invalid @enderror" id="phone" name="phone" value="{{ old('phone', $address->phone) }}" maxlength="50">
                @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-12">
                <label for="address_line_1" class="form-label">Address Line 1</label>
                <input type="text" class="form-control @error('address_line_1') is-invalid @enderror" id="address_line_1" name="address_line_1" value="{{ old('address_line_1', $address->address_line_1) }}" required maxlength="255">
                @error('address_line_1')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-12">
                <label for="address_line_2" class="form-label">Address Line 2</label>
                <input type="text" class="form-control @error('address_line_2') is-invalid @enderror" id="address_line_2" name="address_line_2" value="{{ old('address_line_2', $address->address_line_2) }}" maxlength="255">
                @error('address_line_2')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-5">
                <label for="city" class="form-label">City</label>
                <input type="text" class="form-control @error('city') is-invalid @enderror" id="city" name="city" value="{{ old('city', $address->city) }}" required maxlength="100">
                @error('city')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-3">
                <label for="state" class="form-label">State</label>
                <input type="text" class="form-control @error('state') is-invalid @enderror" id="state" name="state" value="{{ old('state', $address->state) }}" required maxlength="100">
                @error('state')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-4">
                <label for="postal_code" class="form-label">Postal Code</label>
                <input type="text" class="form-control @error('postal_code') is-invalid @enderror" id="postal_code" name="postal_code" value="{{ old('postal_code', $address->postal_code) }}" required maxlength="20" inputmode="numeric">
                @error('postal_code')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6">
                <label for="country" class="form-label">Country</label>
                <input type="text" class="form-control @error('country') is-invalid @enderror" id="country" name="country" value="{{ old('country', $address->country ?? 'US') }}" required maxlength="100">
                @error('country')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        </div>

        <hr class="my-4">

        <div class="form-check mb-2">
            <input type="hidden" name="is_default_shipping" value="0">
            <input type="checkbox" class="form-check-input" id="is_default_shipping" name="is_default_shipping" value="1" {{ old('is_default_shipping', $address->is_default_shipping) ? 'checked' : '' }}>
            <label class="form-check-label" for="is_default_shipping">Use as default shipping address</label>
        </div>
        <div class="form-check">
            <input type="hidden" name="is_default_billing" value="0">
            <input type="checkbox" class="form-check-input" id="is_default_billing" name="is_default_billing" value="1" {{ old('is_default_billing', $address->is_default_billing) ? 'checked' : '' }}>
            <label class="form-check-label" for="is_default_billing">Use as default billing address</label>
        </div>

        <div class="d-flex gap-2 mt-4">
            <button type="submit" class="btn btn-brand">Save Address</button>
            <a href="{{ route('account.addresses.index') }}" class="btn btn-outline-secondary">Cancel</a>
        </div>
    </div>
</div>
