@php
    $errors = $errors ?? session('errors') ?? new \Illuminate\Support\ViewErrorBag();
    $selectedCategories = old('categories', $post->categories->pluck('id')->all());
    $selectedTags = old('tags', $post->tags->pluck('id')->all());
@endphp

<div class="row">
    <div class="col-lg-8">
        <div class="card mb-4">
            <div class="card-body">
                <div class="mb-3">
                    <label for="title" class="form-label">Title <span class="text-danger">*</span></label>
                    <input type="text" class="form-control @error('title') is-invalid @enderror"
                           id="title" name="title" value="{{ old('title', $post->title) }}" required>
                    @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="mb-3">
                    <label for="content" class="form-label">Content</label>
                    <input type="hidden" id="content" name="content" value="{{ old('content', $post->content) }}">
                    <trix-editor input="content" class="form-control @error('content') is-invalid @enderror trix-content"></trix-editor>
                    @error('content')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="mb-3">
                    <label for="excerpt" class="form-label">Excerpt</label>
                    <textarea class="form-control @error('excerpt') is-invalid @enderror"
                              id="excerpt" name="excerpt" rows="3" maxlength="500">{{ old('excerpt', $post->excerpt) }}</textarea>
                    <small class="text-muted">Used in listings, social previews, and search results.</small>
                    @error('excerpt')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="mb-3">
                    <label for="featured_media" class="form-label">Featured Image</label>
                    <input type="file" class="form-control @error('featured_media') is-invalid @enderror"
                           id="featured_media" name="featured_media" accept="image/jpeg,image/png,image/webp">
                    <small class="text-muted">JPEG, PNG, or WebP. Maximum 5MB.</small>
                    @if($post->hasMedia('featured'))
                        <div class="mt-3">
                            <img src="{{ $post->getFirstMediaUrl('featured', 'thumb') }}" alt="Current featured image" class="img-thumbnail" style="max-width: 220px;">
                            <div class="form-check mt-2">
                                <input type="checkbox" class="form-check-input" id="remove_featured_media" name="remove_featured_media" value="1">
                                <label class="form-check-label" for="remove_featured_media">Remove featured image</label>
                            </div>
                        </div>
                    @endif
                    @error('featured_media')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="mb-3">
                    <label for="alt_text" class="form-label">Alt Text</label>
                    <input type="text" class="form-control @error('alt_text') is-invalid @enderror"
                           id="alt_text" name="alt_text" value="{{ old('alt_text', $alt_text ?? '') }}" maxlength="255">
                    @error('alt_text')<div class="invalid-feedback">{{ $message }}</div>@enderror
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
                    <select class="form-select @error('status') is-invalid @enderror" id="status" name="status">
                        @foreach($statuses as $status)
                            <option value="{{ $status->value }}" {{ old('status', $post->status->value) === $status->value ? 'selected' : '' }}>
                                {{ $status->label() }}
                            </option>
                        @endforeach
                    </select>
                    @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="mb-3">
                    <label for="published_at" class="form-label">Publish Date</label>
                    <input type="datetime-local" class="form-control @error('published_at') is-invalid @enderror"
                           id="published_at" name="published_at"
                           value="{{ old('published_at', $post->published_at?->format('Y-m-d\TH:i')) }}">
                    <small class="text-muted">Required for scheduled posts.</small>
                    @error('published_at')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="mb-3">
                    <label for="author_id" class="form-label">Author</label>
                    <select class="form-select @error('author_id') is-invalid @enderror" id="author_id" name="author_id">
                        <option value="">SOGRATE</option>
                        @foreach($authors as $author)
                            <option value="{{ $author->id }}" {{ (string) old('author_id', $post->author_id) === (string) $author->id ? 'selected' : '' }}>
                                {{ $author->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('author_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="form-check mb-3">
                    <input type="hidden" name="comments_enabled" value="0">
                    <input type="checkbox" class="form-check-input" id="comments_enabled" name="comments_enabled" value="1"
                        {{ old('comments_enabled', $post->comments_enabled) ? 'checked' : '' }}>
                    <label class="form-check-label" for="comments_enabled">Allow comments</label>
                </div>

                <div class="d-grid">
                    <button type="submit" class="btn btn-primary">Save Post</button>
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header">Categories</div>
            <div class="card-body">
                <select class="form-select @error('categories') is-invalid @enderror" id="categories" name="categories[]" multiple size="6">
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" {{ in_array($category->id, array_map('intval', (array) $selectedCategories)) ? 'selected' : '' }}>
                            {{ $category->name }}
                        </option>
                    @endforeach
                </select>
                @error('categories')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header">Tags</div>
            <div class="card-body">
                <select class="form-select @error('tags') is-invalid @enderror" id="tags" name="tags[]" multiple size="6">
                    @foreach($tags as $tag)
                        <option value="{{ $tag->id }}" {{ in_array($tag->id, array_map('intval', (array) $selectedTags)) ? 'selected' : '' }}>
                            {{ $tag->name }}
                        </option>
                    @endforeach
                </select>
                @error('tags')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header">SEO Settings</div>
            <div class="card-body">
                <div class="mb-3">
                    <label for="seo_title" class="form-label">SEO Title</label>
                    <input type="text" class="form-control @error('seo_title') is-invalid @enderror"
                           id="seo_title" name="seo_title" value="{{ old('seo_title', $post->seo_title) }}" maxlength="70">
                    @error('seo_title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="mb-3">
                    <label for="seo_description" class="form-label">SEO Description</label>
                    <textarea class="form-control @error('seo_description') is-invalid @enderror"
                              id="seo_description" name="seo_description" rows="3" maxlength="160">{{ old('seo_description', $post->seo_description) }}</textarea>
                    @error('seo_description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
        </div>
    </div>
</div>
