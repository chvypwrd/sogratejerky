@php
    $errors = $errors ?? session('errors') ?? new \Illuminate\Support\ViewErrorBag();
@endphp

<div class="row">
    <div class="col-lg-8">
        <div class="card mb-4">
            <div class="card-body">
                <div class="mb-3">
                    <label for="name" class="form-label">Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control @error('name') is-invalid @enderror"
                           id="name" name="name"
                           value="{{ old('name', $testimonial->name) }}" required>
                    @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="quote" class="form-label">Quote <span class="text-danger">*</span></label>
                    <textarea class="form-control @error('quote') is-invalid @enderror"
                              id="quote" name="quote" rows="5" required>{{ old('quote', $testimonial->quote) }}</textarea>
                    <small class="text-muted">Max 2000 characters.</small>
                    @error('quote')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="attribution" class="form-label">Attribution</label>
                    <input type="text" class="form-control @error('attribution') is-invalid @enderror"
                           id="attribution" name="attribution"
                           value="{{ old('attribution', $testimonial->attribution) }}" maxlength="255">
                    <small class="text-muted">e.g. Member since 2023, Parent of student.</small>
                    @error('attribution')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card mb-4">
            <div class="card-header">Details</div>
            <div class="card-body">
                <div class="mb-3 form-check">
                    <input type="checkbox" class="form-check-input" id="featured" name="featured" value="1"
                        {{ old('featured', $testimonial->featured) ? 'checked' : '' }}>
                    <label class="form-check-label" for="featured">Featured</label>
                </div>

                <div class="mb-3 form-check">
                    <input type="checkbox" class="form-check-input" id="active" name="active" value="1"
                        {{ old('active', $testimonial->active ?? true) ? 'checked' : '' }}>
                    <label class="form-check-label" for="active">Active</label>
                </div>

                <div class="mb-3">
                    <label for="sort_order" class="form-label">Sort Order</label>
                    <input type="number" class="form-control @error('sort_order') is-invalid @enderror"
                           id="sort_order" name="sort_order"
                           value="{{ old('sort_order', $testimonial->sort_order) }}" min="0">
                    <small class="text-muted">Controls ordering. Lower numbers appear first.</small>
                    @error('sort_order')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="d-grid">
                    <button type="submit" class="btn btn-primary">Save Testimonial</button>
                </div>
            </div>
        </div>
    </div>
</div>
