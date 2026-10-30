<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">

    <title>Two-Factor Challenge | {{ config('app.name') }}</title>

    @vite(['resources/scss/app.scss', 'resources/js/app.js'])
</head>
<body>
    <div class="min-vh-100 d-flex align-items-center justify-content-center" style="background-color: #FAF8F5;">
        <div class="w-100" style="max-width: 400px;">
            <div class="text-center mb-4">
                <a href="{{ route('home') }}" class="text-decoration-none text-primary">
                    <h1 class="fs-4 fw-bold">{{ config('app.name') }}</h1>
                </a>
            </div>

            <div class="card shadow-sm">
                <div class="card-body p-4">
                    <h2 class="fs-5 fw-semibold mb-4">Two-Factor Challenge</h2>

                    <p class="text-muted mb-3">
                        Please confirm your password and enter the authentication code from your authenticator app.
                    </p>

                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="POST" action="{{ url('/admin/two-factor/challenge') }}">
                        @csrf

                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input
                                type="password"
                                class="form-control @error('password') is-invalid @enderror"
                                id="password"
                                name="password"
                                required
                                autocomplete="current-password"
                                autofocus
                            >
                            @error('password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="code" class="form-label">Authentication Code</label>
                            <input
                                type="text"
                                class="form-control @error('code') is-invalid @enderror"
                                id="code"
                                name="code"
                                autocomplete="one-time-code"
                                autofocus
                            >
                            @error('code')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <button type="submit" class="btn btn-primary w-100">
                            Authenticate
                        </button>

                        <div class="text-center mt-3">
                            <a href="{{ url('/admin/two-factor/recovery') }}" class="small text-primary" id="recovery-link">
                                Use a recovery code
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
