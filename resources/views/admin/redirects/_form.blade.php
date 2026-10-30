@php
    $errors = $errors ?? session('errors') ?? new \Illuminate\Support\ViewErrorBag();
@endphp

<div class="row">
    <div class="col-lg-8">
        <div class="card mb-4">
            <div class="card-body">
                <div class="mb-3">
                    <label for="old_path" class="form-label">Old Path <span class="text-danger">*</span></label>
                    <input type="text" class="form-control @error('old_path') is-invalid @enderror"
                           id="old_path" name="old_path"
                           value="{{ old('old_path', $redirect->old_path) }}" required maxlength="2048" placeholder="/old-page">
                    <small class="text-muted">Must start with /</small>
                    @error('old_path')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="new_path" class="form-label">New Path <span class="text-danger">*</span></label>
                    <input type="text" class="form-control @error('new_path') is-invalid @enderror"
                           id="new_path" name="new_path"
                           value="{{ old('new_path', $redirect->new_path) }}" required maxlength="2048" placeholder="/new-page">
                    <small class="text-muted">Must start with /</small>
                    @error('new_path')
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
                    <label for="status_code" class="form-label">Status Code <span class="text-danger">*</span></label>
                    <select class="form-select @error('status_code') is-invalid @enderror"
                            id="status_code" name="status_code" required>
                        <option value="301" {{ old('status_code', $redirect->status_code ?? 301) == 301 ? 'selected' : '' }}>301 - Permanent</option>
                        <option value="302" {{ old('status_code', $redirect->status_code ?? 301) == 302 ? 'selected' : '' }}>302 - Temporary</option>
                    </select>
                    @error('status_code')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3 form-check">
                    <input type="checkbox" class="form-check-input" id="active" name="active" value="1"
                        {{ old('active', $redirect->active ?? true) ? 'checked' : '' }}>
                    <label class="form-check-label" for="active">Active</label>
                </div>

                <div class="d-grid">
                    <button type="submit" class="btn btn-primary">Save Redirect</button>
                </div>
            </div>
        </div>
    </div>
</div>
