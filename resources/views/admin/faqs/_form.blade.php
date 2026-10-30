@php
    $errors = $errors ?? session('errors') ?? new \Illuminate\Support\ViewErrorBag();
@endphp

<div class="row">
    <div class="col-lg-8">
        <div class="card mb-4">
            <div class="card-body">
                <div class="mb-3">
                    <label for="question" class="form-label">Question <span class="text-danger">*</span></label>
                    <input type="text" class="form-control @error('question') is-invalid @enderror"
                           id="question" name="question"
                           value="{{ old('question', $faq->question) }}" required maxlength="500">
                    @error('question')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="answer" class="form-label">Answer <span class="text-danger">*</span></label>
                    <input type="hidden" id="answer" name="answer" value="{{ old('answer', $faq->answer) }}">
                    <trix-editor input="answer" class="form-control @error('answer') is-invalid @enderror trix-content" rows="15"></trix-editor>
                    @error('answer')
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
                    <label for="category" class="form-label">Category</label>
                    <input type="text" class="form-control @error('category') is-invalid @enderror"
                           id="category" name="category"
                           value="{{ old('category', $faq->category) }}" maxlength="100">
                    <small class="text-muted">e.g. General, Pricing, Classes, Schedule.</small>
                    @error('category')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3 form-check">
                    <input type="checkbox" class="form-check-input" id="active" name="active" value="1"
                        {{ old('active', $faq->active ?? true) ? 'checked' : '' }}>
                    <label class="form-check-label" for="active">Active</label>
                </div>

                <div class="mb-3">
                    <label for="sort_order" class="form-label">Sort Order</label>
                    <input type="number" class="form-control @error('sort_order') is-invalid @enderror"
                           id="sort_order" name="sort_order"
                           value="{{ old('sort_order', $faq->sort_order) }}" min="0">
                    <small class="text-muted">Controls ordering. Lower numbers appear first.</small>
                    @error('sort_order')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="d-grid">
                    <button type="submit" class="btn btn-primary">Save FAQ</button>
                </div>
            </div>
        </div>
    </div>
</div>
