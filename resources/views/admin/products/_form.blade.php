@php
    $errors = $errors ?? session('errors') ?? new \Illuminate\Support\ViewErrorBag();
    $selectedCategories = old('categories', $product->categories->pluck('id')->all());
    $selectedTags = old('tags', $product->tags->pluck('id')->all());
@endphp

<div class="row">
    <div class="col-lg-8">
        <div class="card mb-4">
            <div class="card-body">
                <div class="mb-3">
                    <label for="name" class="form-label">Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $product->name) }}" required maxlength="255">
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="mb-3">
                    <label for="short_description" class="form-label">Short Description</label>
                    <textarea class="form-control @error('short_description') is-invalid @enderror" id="short_description" name="short_description" rows="3" maxlength="500">{{ old('short_description', $product->short_description) }}</textarea>
                    <small class="text-muted">Used on catalog cards and as an SEO fallback.</small>
                    @error('short_description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="mb-3">
                    <label for="description" class="form-label">Description</label>
                    <input type="hidden" id="description" name="description" value="{{ old('description', $product->description) }}">
                    <trix-editor input="description" class="form-control @error('description') is-invalid @enderror trix-content"></trix-editor>
                    @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="mb-3">
                    <label for="primary_image" class="form-label">Primary Image</label>
                    <input type="file" class="form-control @error('primary_image') is-invalid @enderror" id="primary_image" name="primary_image" accept="image/jpeg,image/png,image/webp">
                    @if($product->hasMedia('primary_image'))
                        <div class="mt-2">
                            <img src="{{ $product->getFirstMediaUrl('primary_image', 'thumb') }}" alt="Current primary image" class="img-thumbnail" style="max-width: 220px;">
                            <div class="form-check mt-2">
                                <input type="checkbox" class="form-check-input" id="remove_primary_image" name="remove_primary_image" value="1">
                                <label class="form-check-label" for="remove_primary_image">Remove primary image</label>
                            </div>
                        </div>
                    @endif
                    @error('primary_image')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="mb-3">
                    <label for="alt_text" class="form-label">Primary Image Alt Text</label>
                    <input type="text" class="form-control @error('alt_text') is-invalid @enderror" id="alt_text" name="alt_text" value="{{ old('alt_text', $alt_text ?? '') }}" maxlength="255">
                    @error('alt_text')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="mb-3">
                    <label for="gallery_images" class="form-label">Gallery Images</label>
                    <input type="file" class="form-control @error('gallery_images') is-invalid @enderror" id="gallery_images" name="gallery_images[]" accept="image/jpeg,image/png,image/webp" multiple>
                    @if($product->getMedia('gallery')->count() > 0)
                        <div class="d-flex flex-wrap gap-2 mt-2">
                            @foreach($product->getMedia('gallery') as $media)
                                <img src="{{ $media->getUrl('thumb') }}" alt="{{ $product->name }}" class="img-thumbnail" style="max-width: 120px;">
                            @endforeach
                        </div>
                        <div class="form-check mt-2">
                            <input type="checkbox" class="form-check-input" id="remove_gallery_images" name="remove_gallery_images" value="1">
                            <label class="form-check-label" for="remove_gallery_images">Remove all gallery images</label>
                        </div>
                    @endif
                    @error('gallery_images')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card mb-4">
            <div class="card-header">Publishing</div>
            <div class="card-body">
                <div class="form-check mb-2">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" class="form-check-input" id="is_active" name="is_active" value="1" {{ old('is_active', $product->is_active) ? 'checked' : '' }}>
                    <label class="form-check-label" for="is_active">Active</label>
                </div>
                <div class="form-check mb-3">
                    <input type="hidden" name="is_featured" value="0">
                    <input type="checkbox" class="form-check-input" id="is_featured" name="is_featured" value="1" {{ old('is_featured', $product->is_featured) ? 'checked' : '' }}>
                    <label class="form-check-label" for="is_featured">Featured on homepage</label>
                </div>
                <div class="mb-3">
                    <label for="sort_order" class="form-label">Sort Order</label>
                    <input type="number" class="form-control @error('sort_order') is-invalid @enderror" id="sort_order" name="sort_order" value="{{ old('sort_order', $product->sort_order ?? 0) }}" min="0">
                    @error('sort_order')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="d-grid">
                    <button type="submit" class="btn btn-primary">Save Product</button>
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header">Categories</div>
            <div class="card-body">
                <select class="form-select @error('categories') is-invalid @enderror" id="categories" name="categories[]" multiple size="6">
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" {{ in_array($category->id, array_map('intval', (array) $selectedCategories)) ? 'selected' : '' }}>{{ $category->name }}</option>
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
                        <option value="{{ $tag->id }}" {{ in_array($tag->id, array_map('intval', (array) $selectedTags)) ? 'selected' : '' }}>{{ $tag->name }}</option>
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
                    <input type="text" class="form-control @error('seo_title') is-invalid @enderror" id="seo_title" name="seo_title" value="{{ old('seo_title', $product->seo_title) }}" maxlength="70">
                    @error('seo_title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="mb-3">
                    <label for="seo_description" class="form-label">SEO Description</label>
                    <textarea class="form-control @error('seo_description') is-invalid @enderror" id="seo_description" name="seo_description" rows="3" maxlength="160">{{ old('seo_description', $product->seo_description) }}</textarea>
                    @error('seo_description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
        </div>
    </div>
</div>
