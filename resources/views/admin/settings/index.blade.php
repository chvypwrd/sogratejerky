@extends('admin.layouts.app')

@section('title', 'Settings')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Settings</h1>
    @if(!app()->isProduction())
        <div class="d-flex gap-2">
            <form action="{{ route('admin.settings.populate') }}" method="POST"
                  onsubmit="return confirm('This will create sample pages, classes, instructors, galleries, testimonials, and FAQs. Continue?')">
                @csrf
                <button type="submit" class="btn btn-outline-warning btn-sm">Populate Sample Content</button>
            </form>
            <form action="{{ route('admin.settings.reset') }}" method="POST"
                  onsubmit="return confirm('This will DROP all tables and re-run migrations with seeders. All data will be lost! Continue?')">
                @csrf
                <button type="submit" class="btn btn-outline-danger btn-sm">Reset &amp; Seed Database</button>
            </form>
        </div>
    @endif
</div>

<form action="{{ route('admin.settings.update') }}" method="POST" enctype="multipart/form-data">
    @csrf
    @method('PUT')

    @foreach($registry as $group => $groupConfig)
        <div class="card mb-4">
            <div class="card-header">{{ ucfirst($group) }}</div>
            <div class="card-body">
                @foreach($groupConfig as $key => $config)
                    <div class="mb-3">
                        <label for="settings_{{ $key }}" class="form-label">{{ $config['label'] }}</label>

                        @if($config['type'] === 'text')
                            <textarea class="form-control @error("settings.{$key}") is-invalid @enderror"
                                      id="settings_{{ $key }}"
                                      name="settings[{{ $key }}]"
                                      rows="3">{{ old("settings.{$key}", $settings[$group][$key] ?? $config['default']) }}</textarea>
                        @elseif($config['type'] === 'email')
                            <input type="email" class="form-control @error("settings.{$key}") is-invalid @enderror"
                                   id="settings_{{ $key }}"
                                   name="settings[{{ $key }}]"
                                   value="{{ old("settings.{$key}", $settings[$group][$key] ?? $config['default']) }}">
                        @elseif($config['type'] === 'url')
                            <input type="url" class="form-control @error("settings.{$key}") is-invalid @enderror"
                                   id="settings_{{ $key }}"
                                   name="settings[{{ $key }}]"
                                   value="{{ old("settings.{$key}", $settings[$group][$key] ?? $config['default']) }}">
                        @elseif($config['type'] === 'phone')
                            <input type="tel" class="form-control @error("settings.{$key}") is-invalid @enderror"
                                   id="settings_{{ $key }}"
                                   name="settings[{{ $key }}]"
                                   value="{{ old("settings.{$key}", $settings[$group][$key] ?? $config['default']) }}">
                        @else
                            <input type="text" class="form-control @error("settings.{$key}") is-invalid @enderror"
                                   id="settings_{{ $key }}"
                                   name="settings[{{ $key }}]"
                                   value="{{ old("settings.{$key}", $settings[$group][$key] ?? $config['default']) }}">
                        @endif

                        @error("settings.{$key}")
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                @endforeach
            </div>
        </div>
    @endforeach

    <div class="card mb-4">
        <div class="card-header">Branding</div>
        <div class="card-body">
            <div class="mb-3">
                <label for="logo" class="form-label">Logo</label>
                @if($branding->hasMedia('logo'))
                    <div class="mb-2">
                        <img src="{{ $branding->getFirstMediaUrl('logo', 'thumb') }}" alt="Current logo" class="img-thumbnail" style="max-height: 80px;">
                    </div>
                @endif
                <input type="file" class="form-control" id="logo" name="logo" accept="image/*">
            </div>

            <div class="mb-3">
                <label for="favicon" class="form-label">Favicon</label>
                @if($branding->hasMedia('favicon'))
                    <div class="mb-2">
                        <img src="{{ $branding->getFirstMediaUrl('favicon') }}" alt="Current favicon" class="img-thumbnail" style="max-height: 32px;">
                    </div>
                @endif
                <input type="file" class="form-control" id="favicon" name="favicon" accept="image/*">
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-end">
        <button type="submit" class="btn btn-primary">Save Settings</button>
    </div>
</form>
@endsection
