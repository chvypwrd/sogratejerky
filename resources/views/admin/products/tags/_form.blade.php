<div class="card">
    <div class="card-body">
        <div class="mb-3">
            <label for="name" class="form-label">Name <span class="text-danger">*</span></label>
            <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $tag->name) }}" required maxlength="255">
            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary">Save Tag</button>
            <a href="{{ route('admin.product-tags.index') }}" class="btn btn-outline-secondary">Cancel</a>
        </div>
    </div>
</div>
