@extends('admin.layouts.app')

@section('title', 'Edit Menu Item')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Edit Menu Item</h1>
    <a href="{{ route('admin.menus.edit', $menu) }}" class="btn btn-outline-secondary">Back to {{ $menu->name }}</a>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card mb-4">
            <div class="card-header">Edit Item</div>
            <div class="card-body">
                <form action="{{ route('admin.menus.items.update', ['menu' => $menu, 'menuItem' => $menuItem]) }}" method="POST">
                    @csrf
                    @method('PUT')

                    @include('admin.menus._form', ['menuItem' => $menuItem, 'menu' => $menu])

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                        <a href="{{ route('admin.menus.edit', $menu) }}" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card mb-4">
            <div class="card-header">Item Details</div>
            <div class="card-body">
                <p class="text-muted mb-1"><strong>Type:</strong> {{ $menuItem->type }}</p>
                <p class="text-muted mb-1"><strong>Status:</strong> {{ $menuItem->enabled ? 'Enabled' : 'Disabled' }}</p>
                @if($menuItem->parent)
                    <p class="text-muted mb-1"><strong>Parent:</strong> {{ $menuItem->parent->label }}</p>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
