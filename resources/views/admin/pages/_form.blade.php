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
                           value="{{ old('title', $page->title) }}" required>
                    @error('title')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="slug" class="form-label">Slug</label>
                    <input type="text" class="form-control text-muted"
                           id="slug" value="{{ $page->slug ?? 'Auto-generated from title' }}" disabled>
                    <small class="text-muted">Slug is generated automatically from the title.</small>
                </div>

                <div class="mb-3">
                    <label for="body" class="form-label">Body</label>
                    <input type="hidden" id="body" name="body" value="{{ old('body', $page->body) }}">
                    <trix-editor input="body" class="form-control @error('body') is-invalid @enderror trix-content" rows="15"></trix-editor>
                    @error('body')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="excerpt" class="form-label">Excerpt</label>
                    <textarea class="form-control @error('excerpt') is-invalid @enderror"
                              id="excerpt" name="excerpt" rows="3">{{ old('excerpt', $page->excerpt) }}</textarea>
                    <small class="text-muted">Brief summary for previews and social sharing (max 500 characters).</small>
                    @error('excerpt')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="featured_media" class="form-label">Featured Image</label>
                    <input type="file" class="form-control @error('featured_media') is-invalid @enderror"
                           id="featured_media" name="featured_media" accept="image/jpeg,image/png,image/webp">
                    <small class="text-muted">Accepted formats: JPEG, PNG, WebP. Max 5MB. Dimensions: 8000x8000.</small>
                    @if($page->hasMedia('featured'))
                        <div class="mt-2">
                            <img src="{{ $page->getFirstMediaUrl('featured', 'thumb') }}" alt="Current featured image" class="img-thumbnail" style="max-width: 200px;">
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

                <div class="mb-3">
                    <label for="sort_order" class="form-label">Sort Order</label>
                    <input type="number" class="form-control @error('sort_order') is-invalid @enderror"
                           id="sort_order" name="sort_order"
                           value="{{ old('sort_order', $page->sort_order) }}" min="0">
                    <small class="text-muted">Controls page ordering. Lower numbers appear first.</small>
                    @error('sort_order')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card mb-4">
            <div class="card-header">Publishing</div>
            <div class="card-body">
                <div class="mb-3">
                    <label for="status" class="form-label">Status</label>
                    <select class="form-select @error('status') is-invalid @enderror"
                            id="status" name="status">
                        @foreach($statuses as $status)
                            <option value="{{ $status->value }}"
                                {{ old('status', $page->status->value) === $status->value ? 'selected' : '' }}>
                                {{ $status->label() }}
                            </option>
                        @endforeach
                    </select>
                    @error('status')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="published_at" class="form-label">Publish Date</label>
                    <input type="datetime-local" class="form-control @error('published_at') is-invalid @enderror"
                           id="published_at" name="published_at"
                           value="{{ old('published_at', $page->published_at?->format('Y-m-d\TH:i')) }}">
                    @error('published_at')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="d-grid">
                    <button type="submit" class="btn btn-primary">Save Page</button>
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header">Template</div>
            <div class="card-body">
                <div class="mb-3">
                    <label for="template" class="form-label">Page Template</label>
                    <select class="form-select @error('template') is-invalid @enderror"
                            id="template" name="template">
                        @foreach($templates as $template)
                            <option value="{{ $template->value }}"
                                {{ old('template', $page->template->value) === $template->value ? 'selected' : '' }}>
                                {{ $template->label() }}
                            </option>
                        @endforeach
                    </select>
                    @error('template')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
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
                           value="{{ old('meta_title', $page->meta_title) }}"
                           maxlength="70">
                    <small class="text-muted">Recommended: 50-60 characters. Max 70.</small>
                    @error('meta_title')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="meta_description" class="form-label">Meta Description</label>
                    <textarea class="form-control @error('meta_description') is-invalid @enderror"
                              id="meta_description" name="meta_description"
                              rows="3" maxlength="160">{{ old('meta_description', $page->meta_description) }}</textarea>
                    <small class="text-muted">Recommended: 150-160 characters. Max 160.</small>
                    @error('meta_description')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="canonical_url" class="form-label">Canonical URL</label>
                    <input type="url" class="form-control @error('canonical_url') is-invalid @enderror"
                           id="canonical_url" name="canonical_url"
                           value="{{ old('canonical_url', $page->canonical_url) }}" maxlength="2048">
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
                                {{ old('robots', $page->robots->value) === $directive->value ? 'selected' : '' }}>
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
