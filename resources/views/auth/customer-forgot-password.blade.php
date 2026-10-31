@extends('layouts.app')

@section('title', 'Forgot Password')

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
                    <h1>Reset Password</h1>
                    <p class="lead text-muted">Enter your email address and we will send a secure reset link.</p>
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

                        <form method="POST" action="{{ route('customer.password.email') }}">
                            @csrf
                            <div class="mb-3">
                                <label for="email" class="form-label">Email Address</label>
                                <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email') }}" required autocomplete="email" autofocus>
                                @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <button type="submit" class="btn btn-brand w-100">Send Reset Link</button>
                        </form>

                        <div class="account-auth-links">
                            <a href="{{ route('customer.login') }}">Back to sign in</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
