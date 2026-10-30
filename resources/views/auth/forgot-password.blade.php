<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">

    <title>Forgot Password | {{ config('app.name') }}</title>

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
                    <h2 class="fs-5 fw-semibold mb-4">Reset Password</h2>

                    <p class="text-muted mb-3">
                        Enter your email address and we will send you a link to reset your password.
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

                    @if (session('status'))
                        <div class="alert alert-success">
                            {{ session('status') }}
                        </div>
                    @endif

                    <form method="POST" action="{{ route('password.email') }}">
                        @csrf

                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address</label>
                            <input
                                type="email"
                                class="form-control @error('email') is-invalid @enderror"
                                id="email"
                                name="email"
                                value="{{ old('email') }}"
                                required
                                autocomplete="email"
                                autofocus
                            >
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <button type="submit" class="btn btn-primary w-100">
                            Send Password Reset Link
                        </button>

                        <div class="text-center mt-3">
                            <a href="{{ route('login') }}" class="small text-primary">
                                Back to Login
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
