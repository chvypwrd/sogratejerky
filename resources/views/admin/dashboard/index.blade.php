@extends('admin.layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="mb-4">
    <h1>Dashboard</h1>
    <p class="text-muted">Welcome, {{ Auth::user()->name }}</p>
</div>

<div class="row g-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Website Administration</h5>
                <p class="card-text text-muted">
                    Manage your SOGRATE website from this admin panel.
                </p>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Account Security</h5>
                <p class="card-text text-muted">
                    <a href="{{ route('admin.security') }}">Manage your two-factor authentication</a>
                    and account security settings.
                </p>
            </div>
        </div>
    </div>
</div>
@endsection
