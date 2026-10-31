@php
    $field = fn (string $name): string => $prefix.'_'.$name;
    $value = fn (string $name): ?string => old($field($name));
@endphp

<div class="row g-3">
    <div class="col-md-6">
        <label for="{{ $prefix }}-recipient_name" class="form-label">Recipient name</label>
        <input type="text" class="form-control @error($field('recipient_name')) is-invalid @enderror" id="{{ $prefix }}-recipient_name" name="{{ $field('recipient_name') }}" value="{{ $value('recipient_name') }}" autocomplete="name">
        @error($field('recipient_name'))<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6">
        <label for="{{ $prefix }}-company" class="form-label">Company <span class="text-muted">(optional)</span></label>
        <input type="text" class="form-control @error($field('company')) is-invalid @enderror" id="{{ $prefix }}-company" name="{{ $field('company') }}" value="{{ $value('company') }}" autocomplete="organization">
        @error($field('company'))<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-12">
        <label for="{{ $prefix }}-address_line_1" class="form-label">Address line 1</label>
        <input type="text" class="form-control @error($field('address_line_1')) is-invalid @enderror" id="{{ $prefix }}-address_line_1" name="{{ $field('address_line_1') }}" value="{{ $value('address_line_1') }}" autocomplete="address-line1">
        @error($field('address_line_1'))<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-12">
        <label for="{{ $prefix }}-address_line_2" class="form-label">Address line 2 <span class="text-muted">(optional)</span></label>
        <input type="text" class="form-control @error($field('address_line_2')) is-invalid @enderror" id="{{ $prefix }}-address_line_2" name="{{ $field('address_line_2') }}" value="{{ $value('address_line_2') }}" autocomplete="address-line2">
        @error($field('address_line_2'))<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-5">
        <label for="{{ $prefix }}-city" class="form-label">City</label>
        <input type="text" class="form-control @error($field('city')) is-invalid @enderror" id="{{ $prefix }}-city" name="{{ $field('city') }}" value="{{ $value('city') }}" autocomplete="address-level2">
        @error($field('city'))<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-3">
        <label for="{{ $prefix }}-state" class="form-label">State</label>
        <input type="text" class="form-control @error($field('state')) is-invalid @enderror" id="{{ $prefix }}-state" name="{{ $field('state') }}" value="{{ $value('state') }}" autocomplete="address-level1">
        @error($field('state'))<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4">
        <label for="{{ $prefix }}-postal_code" class="form-label">ZIP code</label>
        <input type="text" class="form-control @error($field('postal_code')) is-invalid @enderror" id="{{ $prefix }}-postal_code" name="{{ $field('postal_code') }}" value="{{ $value('postal_code') }}" autocomplete="postal-code">
        @error($field('postal_code'))<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6">
        <label for="{{ $prefix }}-country" class="form-label">Country</label>
        <input type="text" class="form-control @error($field('country')) is-invalid @enderror" id="{{ $prefix }}-country" name="{{ $field('country') }}" value="{{ $value('country') ?? 'United States' }}" autocomplete="country-name">
        @error($field('country'))<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6">
        <label for="{{ $prefix }}-phone" class="form-label">Phone <span class="text-muted">(optional)</span></label>
        <input type="text" class="form-control @error($field('phone')) is-invalid @enderror" id="{{ $prefix }}-phone" name="{{ $field('phone') }}" value="{{ $value('phone') }}" autocomplete="tel">
        @error($field('phone'))<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
</div>
