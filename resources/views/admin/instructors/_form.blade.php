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
                           value="{{ old('name', $instructor->name) }}" required>
                    @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="title" class="form-label">Title</label>
                    <input type="text" class="form-control @error('title') is-invalid @enderror"
                           id="title" name="title"
                           value="{{ old('title', $instructor->title) }}" maxlength="255">
                    <small class="text-muted">e.g. Head Instructor, Black Belt Coach.</small>
                    @error('title')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="short_bio" class="form-label">Short Bio</label>
                    <textarea class="form-control @error('short_bio') is-invalid @enderror"
                              id="short_bio" name="short_bio" rows="3">{{ old('short_bio', $instructor->short_bio) }}</textarea>
                    <small class="text-muted">Brief bio for listings (max 500 characters).</small>
                    @error('short_bio')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="bio" class="form-label">Bio <span class="text-danger">*</span></label>
                    <input type="hidden" id="bio" name="bio" value="{{ old('bio', $instructor->bio) }}">
                    <trix-editor input="bio" class="form-control @error('bio') is-invalid @enderror trix-content" rows="15"></trix-editor>
                    @error('bio')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="featured_media" class="form-label">Portrait Image</label>
                    <input type="file" class="form-control @error('featured_media') is-invalid @enderror"
                           id="featured_media" name="featured_media" accept="image/jpeg,image/png,image/webp">
                    <small class="text-muted">Accepted formats: JPEG, PNG, WebP. Max 5MB. Dimensions: 8000x8000.</small>
                    @if($instructor->hasMedia('portrait'))
                        <div class="mt-2">
                            <img src="{{ $instructor->getFirstMediaUrl('portrait', 'thumb') }}" alt="Current portrait" class="img-thumbnail" style="max-width: 200px;">
                            <div class="form-check mt-2">
                                <input type="checkbox" class="form-check-input" id="remove_featured_media" name="remove_featured_media" value="1">
                                <label class="form-check-label" for="remove_featured_media">Remove portrait image</label>
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
                    <small class="text-muted">Describe the portrait image for accessibility and SEO.</small>
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
                <div class="mb-3 form-check">
                    <input type="checkbox" class="form-check-input" id="active" name="active" value="1"
                        {{ old('active', $instructor->active ?? true) ? 'checked' : '' }}>
                    <label class="form-check-label" for="active">Active</label>
                </div>

                <div class="mb-3">
                    <label for="sort_order" class="form-label">Sort Order</label>
                    <input type="number" class="form-control @error('sort_order') is-invalid @enderror"
                           id="sort_order" name="sort_order"
                           value="{{ old('sort_order', $instructor->sort_order) }}" min="0">
                    <small class="text-muted">Controls ordering. Lower numbers appear first.</small>
                    @error('sort_order')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="d-grid">
                    <button type="submit" class="btn btn-primary">Save Instructor</button>
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
                           value="{{ old('meta_title', $instructor->meta_title) }}"
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
                              rows="3">{{ old('meta_description', $instructor->meta_description) }}</textarea>
                    <small class="text-muted">Recommended: 150-160 characters.</small>
                    @error('meta_description')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="canonical_url" class="form-label">Canonical URL</label>
                    <input type="url" class="form-control @error('canonical_url') is-invalid @enderror"
                           id="canonical_url" name="canonical_url"
                           value="{{ old('canonical_url', $instructor->canonical_url) }}" maxlength="2048">
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
                                {{ old('robots', $instructor->robots->value) === $directive->value ? 'selected' : '' }}>
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
