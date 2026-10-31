@extends('layouts.app')

@section('title', 'Verify Your Email')

@section('meta')
    <meta name="robots" content="noindex,follow">
@endsection

@section('content')
<section class="py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <header class="page-header">
                    <span class="eyebrow">Account</span>
                    <h1>Verify Your Email</h1>
                    <p class="lead text-muted">Please verify your email address using the link we sent you.</p>
                </header>

                @if(session('status'))
                    <div class="alert alert-success" role="status">{{ session('status') }}</div>
                @endif

                <div class="card">
                    <div class="card-body p-4">
                        <p class="mb-4">If you did not receive the email, we can send another verification link.</p>
                        <form method="POST" action="{{ route('verification.send') }}">
                            @csrf
                            <button type="submit" class="btn btn-brand">Resend Verification Email</button>
                        </form>
                        <div class="account-auth-links">
                            <a href="{{ route('account.dashboard') }}">Back to My Account</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
