@php
    $photos = $gallery->getMedia('photos');
@endphp

@if($photos->count() > 0)
<div class="card mb-4">
    <div class="card-header">Photo Manager</div>
    <div class="card-body">
        <div class="row" id="photo-grid">
            @foreach($photos as $photo)
                <div class="col-md-3 mb-3" data-media-id="{{ $photo->id }}">
                    <div class="card h-100">
                        <div class="position-relative">
                            <img src="{{ $photo->getUrl('thumb') }}" alt="{{ $photo->getCustomProperty('alt_text', '') }}" class="card-img-top" style="height: 150px; object-fit: cover;">
                            <div class="position-absolute top-0 end-0 p-1">
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" name="cover_media_id" value="{{ $photo->id }}"
                                        {{ $photo->getCustomProperty('is_cover') ? 'checked' : '' }}>
                                    <label class="form-check-label text-white bg-dark bg-opacity-75 rounded px-1" style="font-size: 0.7rem;">Cover</label>
                                </div>
                            </div>
                        </div>
                        <div class="card-body p-2">
                            <div class="mb-2">
                                <label class="form-label" style="font-size: 0.75rem;">Alt Text</label>
                                <input type="text" class="form-control form-control-sm" name="photo_alt_text[{{ $photo->id }}]"
                                       value="{{ $photo->getCustomProperty('alt_text', '') }}" maxlength="255">
                            </div>
                            <div class="mb-2">
                                <label class="form-label" style="font-size: 0.75rem;">Caption</label>
                                <input type="text" class="form-control form-control-sm" name="photo_caption[{{ $photo->id }}]"
                                       value="{{ $photo->getCustomProperty('caption', '') }}" maxlength="1000">
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                <small class="text-muted" style="font-size: 0.7rem;">{{ $photo->file_name }}</small>
                                <button type="button" class="btn btn-outline-danger btn-sm" style="font-size: 0.7rem;"
                                        onclick="removePhoto({{ $photo->id }})">Remove</button>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
@endif
