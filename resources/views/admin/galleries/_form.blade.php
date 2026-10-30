@php
    $errors = $errors ?? session('errors') ?? new \Illuminate\Support\ViewErrorBag();
@endphp

<div class="row">
    <div class="col-lg-8">
        <div class="card mb-4">
            <div class="card-body">
                <div class="mb-3">
                    <label for="title" class="form-label">Title <span class="text-danger">*</span></label>
                    <input type="text" class="form-control @error('title') is-invalid @enderror"
                           id="title" name="title"
                           value="{{ old('title', $gallery->title) }}" required>
                    @error('title')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="description" class="form-label">Description</label>
                    <textarea class="form-control @error('description') is-invalid @enderror"
                              id="description" name="description" rows="3">{{ old('description', $gallery->description) }}</textarea>
                    <small class="text-muted">Brief description for the gallery (max 2000 characters).</small>
                    @error('description')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="photos" class="form-label">Photos</label>
                    <input type="file" class="form-control @error('photos') is-invalid @enderror"
                           id="photos" name="photos[]" accept="image/jpeg,image/png,image/webp" multiple>
                    <small class="text-muted">Accepted formats: JPEG, PNG, WebP. Max 5MB each. Max 20 files. Dimensions: 8000x8000.</small>
                    @error('photos')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    @error('photos.*')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="alt_text" class="form-label">Alt Text (for uploaded photos)</label>
                    <input type="text" class="form-control @error('alt_text') is-invalid @enderror"
                           id="alt_text" name="alt_text"
                           value="{{ old('alt_text') }}" maxlength="255">
                    <small class="text-muted">Describe the photos for accessibility and SEO.</small>
                    @error('alt_text')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="caption" class="form-label">Caption (for uploaded photos)</label>
                    <input type="text" class="form-control @error('caption') is-invalid @enderror"
                           id="caption" name="caption"
                           value="{{ old('caption') }}" maxlength="1000">
                    <small class="text-muted">Caption displayed with the photos.</small>
                    @error('caption')
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
                    <input type="checkbox" class="form-check-input" id="active" name="active" value="1"
                        {{ old('active', $gallery->active ?? true) ? 'checked' : '' }}>
                    <label class="form-check-label" for="active">Active</label>
                </div>

                <div class="mb-3 form-check">
                    <input type="checkbox" class="form-check-input" id="featured" name="featured" value="1"
                        {{ old('featured', $gallery->featured) ? 'checked' : '' }}>
                    <label class="form-check-label" for="featured">Featured</label>
                </div>

                <div class="mb-3">
                    <label for="sort_order" class="form-label">Sort Order</label>
                    <input type="number" class="form-control @error('sort_order') is-invalid @enderror"
                           id="sort_order" name="sort_order"
                           value="{{ old('sort_order', $gallery->sort_order) }}" min="0">
                    <small class="text-muted">Controls ordering. Lower numbers appear first.</small>
                    @error('sort_order')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="d-grid">
                    <button type="submit" class="btn btn-primary">Save Gallery</button>
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header">SEO Settings</div>
            <div class="card-body">
                <div class="mb-3">
                    <label for="meta_title" class="form-label">Meta Title</label>
                    <input type="text" class="form-control @error('meta_title') is-invalid @enderror"
                           id="meta_title" name="meta_title"
                           value="{{ old('meta_title', $gallery->meta_title) }}"
                           maxlength="255">
                    <small class="text-muted">Recommended: 50-60 characters.</small>
                    @error('meta_title')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="meta_description" class="form-label">Meta Description</label>
                    <textarea class="form-control @error('meta_description') is-invalid @enderror"
                              id="meta_description" name="meta_description"
                              rows="3">{{ old('meta_description', $gallery->meta_description) }}</textarea>
                    <small class="text-muted">Recommended: 150-160 characters.</small>
                    @error('meta_description')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="canonical_url" class="form-label">Canonical URL</label>
                    <input type="url" class="form-control @error('canonical_url') is-invalid @enderror"
                           id="canonical_url" name="canonical_url"
                           value="{{ old('canonical_url', $gallery->canonical_url) }}" maxlength="2048">
                    <small class="text-muted">Leave empty to use the page URL.</small>
                    @error('canonical_url')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="robots" class="form-label">Robots Directive</label>
                    <select class="form-select @error('robots') is-invalid @enderror"
                            id="robots" name="robots">
                        @foreach($robotsDirectives as $directive)
                            <option value="{{ $directive->value }}"
                                {{ old('robots', $gallery->robots->value ?? 'index,follow') === $directive->value ? 'selected' : '' }}>
                                {{ $directive->label() }}
                            </option>
                        @endforeach
                    </select>
                    @error('robots')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>
    </div>
</div>

@if($gallery->exists)
    @include('admin.galleries._photo-manager', ['gallery' => $gallery])
@endif
