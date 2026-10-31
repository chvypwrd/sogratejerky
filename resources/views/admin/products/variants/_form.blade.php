<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-body">
                <div class="mb-3">
                    <label for="name" class="form-label">Variant Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $variant->name) }}" required maxlength="255" placeholder="e.g. Original 2 oz">
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="mb-3">
                    <label for="sku" class="form-label">SKU <span class="text-danger">*</span></label>
                    <input type="text" class="form-control @error('sku') is-invalid @enderror" id="sku" name="sku" value="{{ old('sku', $variant->sku) }}" required maxlength="100">
                    @error('sku')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="price" class="form-label">Price (USD) <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text">$</span>
                            <input type="number" step="0.01" min="0.01" class="form-control @error('price') is-invalid @enderror" id="price" name="price" value="{{ old('price', $variant->price ? number_format($variant->price / 100, 2, '.', '') : '') }}" required>
                            @error('price')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="weight" class="form-label">Weight (grams) <span class="text-danger">*</span></label>
                        <input type="number" min="0" class="form-control @error('weight') is-invalid @enderror" id="weight" name="weight" value="{{ old('weight', $variant->weight ?? 0) }}" required>
                        <small class="text-muted">Stored in grams and shown with an ounce conversion.</small>
                        @error('weight')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="mb-3">
                    <label for="image" class="form-label">Variant Image</label>
                    <input type="file" class="form-control @error('image') is-invalid @enderror" id="image" name="image" accept="image/jpeg,image/png,image/webp">
                    @if($variant->hasMedia('image'))
                        <div class="mt-2">
                            <img src="{{ $variant->getFirstMediaUrl('image', 'thumb') }}" alt="Current variant image" class="img-thumbnail" style="max-width: 160px;">
                            <div class="form-check mt-2">
                                <input type="checkbox" class="form-check-input" id="remove_image" name="remove_image" value="1">
                                <label class="form-check-label" for="remove_image">Remove variant image</label>
                            </div>
                        </div>
                    @endif
                    @error('image')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card mb-4">
            <div class="card-header">Availability</div>
            <div class="card-body">
                <div class="form-check mb-3">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" class="form-check-input" id="is_active" name="is_active" value="1" {{ old('is_active', $variant->is_active) ? 'checked' : '' }}>
                    <label class="form-check-label" for="is_active">Active</label>
                </div>
                <div class="mb-3">
                    <label for="sort_order" class="form-label">Sort Order</label>
                    <input type="number" class="form-control @error('sort_order') is-invalid @enderror" id="sort_order" name="sort_order" value="{{ old('sort_order', $variant->sort_order ?? 0) }}" min="0">
                    @error('sort_order')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="d-grid">
                    <button type="submit" class="btn btn-primary">Save Variant</button>
                </div>
            </div>
        </div>

        @if($variant->exists)
            <div class="card">
                <div class="card-header">Shareable URL</div>
                <div class="card-body">
                    @php
                        $variantUrl = route('shop.show', ['product' => $product->slug, 'variant' => $variant->id]);
                    @endphp
                    <label for="variant_url" class="form-label">Variant URL</label>
                    <div class="input-group">
                        <input type="text" class="form-control" id="variant_url" value="{{ $variantUrl }}" readonly>
                        <button type="button" class="btn btn-outline-secondary" onclick="navigator.clipboard.writeText(document.getElementById('variant_url').value); this.textContent='Copied'; setTimeout(() => this.textContent='Copy', 1500);">Copy</button>
                    </div>
                    <a href="{{ $variantUrl }}" target="_blank" class="small d-inline-block mt-2">Open variant page</a>
                </div>
            </div>
        @endif
    </div>
</div>
