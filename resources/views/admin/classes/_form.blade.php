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
                           value="{{ old('title', $class->title) }}" required>
                    @error('title')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="short_description" class="form-label">Short Description</label>
                    <textarea class="form-control @error('short_description') is-invalid @enderror"
                              id="short_description" name="short_description" rows="3">{{ old('short_description', $class->short_description) }}</textarea>
                    <small class="text-muted">Brief summary for listings and previews (max 500 characters).</small>
                    @error('short_description')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="description" class="form-label">Description <span class="text-danger">*</span></label>
                    <input type="hidden" id="description" name="description" value="{{ old('description', $class->description) }}">
                    <trix-editor input="description" class="form-control @error('description') is-invalid @enderror trix-content" rows="15"></trix-editor>
                    @error('description')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="featured_media" class="form-label">Featured Image</label>
                    <input type="file" class="form-control @error('featured_media') is-invalid @enderror"
                           id="featured_media" name="featured_media" accept="image/jpeg,image/png,image/webp">
                    <small class="text-muted">Accepted formats: JPEG, PNG, WebP. Max 5MB. Dimensions: 8000x8000.</small>
                    @if($class->hasMedia('featured'))
                        <div class="mt-2">
                            <img src="{{ $class->getFirstMediaUrl('featured', 'thumb') }}" alt="Current featured image" class="img-thumbnail" style="max-width: 200px;">
                            <div class="form-check mt-2">
                                <input type="checkbox" class="form-check-input" id="remove_featured_media" name="remove_featured_media" value="1">
                                <label class="form-check-label" for="remove_featured_media">Remove featured image</label>
                            </div>
                        </div>
                    @endif
                    @error('featured_media')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="alt_text" class="form-label">Alt Text</label>
                    <input type="text" class="form-control @error('alt_text') is-invalid @enderror"
                           id="alt_text" name="alt_text"
                           value="{{ old('alt_text', $alt_text ?? '') }}" maxlength="255">
                    <small class="text-muted">Describe the featured image for accessibility and SEO.</small>
                    @error('alt_text')
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
                <div class="mb-3">
                    <label for="level" class="form-label">Level</label>
                    <input type="text" class="form-control @error('level') is-invalid @enderror"
                           id="level" name="level"
                           value="{{ old('level', $class->level) }}" maxlength="100">
                    <small class="text-muted">e.g. Beginner, Intermediate, Advanced.</small>
                    @error('level')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="duration" class="form-label">Duration</label>
                    <input type="text" class="form-control @error('duration') is-invalid @enderror"
                           id="duration" name="duration"
                           value="{{ old('duration', $class->duration) }}" maxlength="100">
                    <small class="text-muted">e.g. 60 minutes, 4 weeks.</small>
                    @error('duration')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3 form-check">
                    <input type="checkbox" class="form-check-input" id="featured" name="featured" value="1"
                        {{ old('featured', $class->featured) ? 'checked' : '' }}>
                    <label class="form-check-label" for="featured">Featured</label>
                </div>

                <div class="mb-3 form-check">
                    <input type="checkbox" class="form-check-input" id="active" name="active" value="1"
                        {{ old('active', $class->active ?? true) ? 'checked' : '' }}>
                    <label class="form-check-label" for="active">Active</label>
                </div>

                <div class="mb-3">
                    <label for="sort_order" class="form-label">Sort Order</label>
                    <input type="number" class="form-control @error('sort_order') is-invalid @enderror"
                           id="sort_order" name="sort_order"
                           value="{{ old('sort_order', $class->sort_order) }}" min="0">
                    <small class="text-muted">Controls ordering. Lower numbers appear first.</small>
                    @error('sort_order')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="d-grid">
                    <button type="submit" class="btn btn-primary">Save Class</button>
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
                           value="{{ old('meta_title', $class->meta_title) }}"
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
                              rows="3">{{ old('meta_description', $class->meta_description) }}</textarea>
                    <small class="text-muted">Recommended: 150-160 characters.</small>
                    @error('meta_description')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="canonical_url" class="form-label">Canonical URL</label>
                    <input type="url" class="form-control @error('canonical_url') is-invalid @enderror"
                           id="canonical_url" name="canonical_url"
                           value="{{ old('canonical_url', $class->canonical_url) }}" maxlength="2048">
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
                                {{ old('robots', $class->robots->value) === $directive->value ? 'selected' : '' }}>
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
