@extends('admin.layouts.app')

@section('title', 'Edit: ' . $page->title)

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Edit Page</h1>
    <div>
        <a href="{{ route('admin.pages.index') }}" class="btn btn-outline-secondary">Back to Pages</a>
        @if($page->exists && $page->isPublishedOrScheduled())
            <a href="{{ route('pages.show', $page->slug) }}" class="btn btn-outline-primary" target="_blank">View Live</a>
        @endif
    </div>
</div>

<form action="{{ route('admin.pages.update', $page) }}" method="POST" enctype="multipart/form-data">
    @csrf
    @method('PUT')
    @include('admin.pages._form', ['page' => $page])
</form>
@endsection
