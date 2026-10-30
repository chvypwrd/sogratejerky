@extends('admin.layouts.app')

@section('title', 'Menus')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Menus</h1>
</div>

<div class="row">
    @foreach($locations as $location)
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">{{ $location->label() }}</h5>
                </div>
                <div class="card-footer">
                    <a href="{{ route('admin.menus.edit', $location->value) }}" class="btn btn-primary btn-sm">Manage Menu</a>
                </div>
            </div>
        </div>
    @endforeach
</div>
@endsection
