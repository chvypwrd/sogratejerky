@extends('layouts.app')

@section('title', 'Sign In')

@section('meta')
    <meta name="robots" content="noindex,follow">
@endsection

@section('content')
<section class="py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-7 col-lg-5">
                <header class="page-header">
                    <span class="eyebrow">Account</span>
                    <h1>Sign In</h1>
                    <p class="lead text-muted">Sign in to access your Sograte account and saved cart.</p>
                </header>

                @if(session('status'))
                    <div class="alert alert-success" role="status">{{ session('status') }}</div>
                @endif

                <div class="card">
                    <div class="card-body p-4">
                        @if($errors->any())
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    @foreach($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <form method="POST" action="{{ route('customer.login.store') }}">
                            @csrf

                            <div class="mb-3">
                                <label for="email" class="form-label">Email Address</label>
                                <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email') }}" required autocomplete="email" autofocus>
                                @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" class="form-control @error('password') is-invalid @enderror" id="password" name="password" required autocomplete="current-password">
                                @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="form-check mb-3">
                                <input type="checkbox" class="form-check-input" id="remember" name="remember" value="1">
                                <label class="form-check-label" for="remember">Remember me</label>
                            </div>

                            <button type="submit" class="btn btn-brand w-100">Sign In</button>
                        </form>

                        <div class="account-auth-links">
                            <a href="{{ route('customer.password.request') }}">Forgot your password?</a>
                            <a href="{{ route('customer.register') }}">Create an account</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
