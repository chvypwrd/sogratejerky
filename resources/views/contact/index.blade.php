@extends('layouts.app')

@section('title', 'Contact Us')

@section('meta')
    <link rel="canonical" href="{{ route('contact.show', [], false) }}">
    <meta property="og:type" content="website">
    <meta property="og:title" content="{{ e(config('app.name')) }} - Contact Us">
    <meta property="og:url" content="{{ route('contact.show', [], false) }}">
@endsection

@section('content')
<section class="py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-9">
                <header class="page-header">
                    <span class="eyebrow">Get in touch</span>
                    <h1>Contact Us</h1>
                    <p class="lead text-muted">Questions about Sograte jerky? Send us a message and we will get back to you.</p>
                </header>

                @if (session('status'))
                    <div class="alert alert-success" role="status">
                        {{ session('status') }}
                    </div>
                @endif

                <div class="card">
                    <div class="card-body p-4 p-md-5">
                        <form method="POST" action="{{ route('contact.store') }}" id="contact-form">
                            @csrf

                            <div class="row g-4">
                                <div class="col-md-6">
                                    <label for="name" class="form-label">Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name') }}" required>
                                    @error('name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6">
                                    <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                                    <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email') }}" required>
                                    @error('email')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6">
                                    <label for="phone" class="form-label">Phone</label>
                                    <input type="tel" class="form-control @error('phone') is-invalid @enderror" id="phone" name="phone" value="{{ old('phone') }}">
                                    @error('phone')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6">
                                    <label for="subject" class="form-label">Subject</label>
                                    <input type="text" class="form-control @error('subject') is-invalid @enderror" id="subject" name="subject" value="{{ old('subject') }}">
                                    @error('subject')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-12">
                                    <label for="message" class="form-label">Message <span class="text-danger">*</span></label>
                                    <textarea class="form-control @error('message') is-invalid @enderror" id="message" name="message" rows="6" required>{{ old('message') }}</textarea>
                                    @error('message')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            {{-- Honeypot --}}
                            <div class="d-none" aria-hidden="true">
                                <label for="website">Leave this empty</label>
                                <input type="text" id="website" name="website" value="" tabindex="-1" autocomplete="off">
                            </div>

                            <input type="hidden" name="timing_token" value="{{ $timingToken }}">

                            <button type="submit" class="btn btn-brand btn-lg mt-4">Send Message</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
