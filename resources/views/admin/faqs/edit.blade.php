@extends('admin.layouts.app')

@section('title', 'Edit FAQ')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Edit FAQ</h1>
    <div>
        <a href="{{ route('admin.faqs.index') }}" class="btn btn-outline-secondary">Back to FAQs</a>
    </div>
</div>

<form action="{{ route('admin.faqs.update', $faq) }}" method="POST">
    @csrf
    @method('PUT')
    @include('admin.faqs._form', ['faq' => $faq])
</form>
@endsection
