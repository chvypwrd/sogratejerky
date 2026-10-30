@php
    $errors = $errors ?? session('errors') ?? new \Illuminate\Support\ViewErrorBag();
    $menuItem = $menuItem ?? null;
    $menu = $menu ?? null;
@endphp

<div class="mb-3">
    <label for="label" class="form-label">Label <span class="text-danger">*</span></label>
    <input type="text" class="form-control @error('label') is-invalid @enderror"
           id="label" name="label"
           value="{{ old('label', $menuItem->label ?? '') }}" required maxlength="255">
    @error('label')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label for="type" class="form-label">Type <span class="text-danger">*</span></label>
    <select class="form-select @error('type') is-invalid @enderror" id="type" name="type" required>
        <option value="custom" {{ old('type', $menuItem->type ?? 'custom') === 'custom' ? 'selected' : '' }}>Custom URL</option>
        <option value="page" {{ old('type', $menuItem->type ?? '') === 'page' ? 'selected' : '' }}>Page</option>
        <option value="class" {{ old('type', $menuItem->type ?? '') === 'class' ? 'selected' : '' }}>Class</option>
        <option value="instructor" {{ old('type', $menuItem->type ?? '') === 'instructor' ? 'selected' : '' }}>Instructor</option>
        <option value="gallery" {{ old('type', $menuItem->type ?? '') === 'gallery' ? 'selected' : '' }}>Gallery</option>
        <option value="home" {{ old('type', $menuItem->type ?? '') === 'home' ? 'selected' : '' }}>Home</option>
    </select>
    @error('type')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3" id="urlField">
    <label for="url" class="form-label">URL</label>
    <input type="text" class="form-control @error('url') is-invalid @enderror"
           id="url" name="url"
           value="{{ old('url', $menuItem->url ?? '') }}" maxlength="2048">
    <small class="text-muted">Internal path only (e.g., /about)</small>
    @error('url')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

@if($menu)
<div class="mb-3">
    <label for="parent_id" class="form-label">Parent Item</label>
    <select class="form-select @error('parent_id') is-invalid @enderror" id="parent_id" name="parent_id">
        <option value="">None (top level)</option>
        @php
            $excludeIds = $menuItem ? array_merge([$menuItem->id], $menuItem->children()->pluck('id')->toArray()) : [];
        @endphp
        @foreach(\App\Models\MenuItem::where('menu_id', $menu->id)->whereNull('parent_id')->whereNotIn('id', $excludeIds)->orderBy('sort_order')->get() as $parentItem)
            <option value="{{ $parentItem->id }}" {{ old('parent_id', $menuItem->parent_id ?? '') == $parentItem->id ? 'selected' : '' }}>
                {{ $parentItem->label }}
            </option>
            @foreach($parentItem->children as $child)
                @if(!in_array($child->id, $excludeIds))
                    <option value="{{ $child->id }}" {{ old('parent_id', $menuItem->parent_id ?? '') == $child->id ? 'selected' : '' }}>
                        &nbsp;&nbsp;{{ $child->label }}
                    </option>
                @endif
            @endforeach
        @endforeach
    </select>
    <small class="text-muted">Leave empty for top-level item. Nesting supports up to 2 levels.</small>
    @error('parent_id')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>
@endif

<div class="mb-3 form-check">
    <input type="hidden" name="enabled" value="0">
    <input type="checkbox" class="form-check-input" id="enabled" name="enabled" value="1"
        {{ old('enabled', $menuItem->enabled ?? true) ? 'checked' : '' }}>
    <label class="form-check-label" for="enabled">Enabled</label>
</div>
