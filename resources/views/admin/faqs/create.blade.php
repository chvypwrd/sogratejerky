@extends('admin.layouts.app')

@section('title', 'Create FAQ')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Create FAQ</h1>
    <a href="{{ route('admin.faqs.index') }}" class="btn btn-outline-secondary">Back to FAQs</a>
</div>

<form action="{{ route('admin.faqs.store') }}" method="POST">
    @csrf
    @include('admin.faqs._form', ['faq' => $faq])
</form>
@endsection
