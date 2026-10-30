@extends('admin.layouts.app')

@section('title', 'Edit Menu: ' . $menu->name)

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">{{ $menu->name }}</h1>
    <a href="{{ route('admin.menus.index') }}" class="btn btn-outline-secondary">Back to Menus</a>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>Menu Items</span>
                <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addItemModal">Add Item</button>
            </div>
            <div class="card-body">
                @if(!empty($items))
                    <ul class="list-group menu-tree" id="menuTree">
                        @foreach($items as $item)
                            @include('admin.menus._tree-item', ['item' => $item, 'depth' => 0])
                        @endforeach
                    </ul>
                @else
                    <div class="text-center py-4">
                        <p class="text-muted mb-3">No menu items yet.</p>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addItemModal">Add your first item</button>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card mb-4">
            <div class="card-header">Menu Details</div>
            <div class="card-body">
                <form action="{{ route('admin.menus.update', $menu) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="mb-3">
                        <label for="name" class="form-label">Menu Name</label>
                        <input type="text" class="form-control" id="name" name="name" value="{{ old('name', $menu->name) }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Location</label>
                        <input type="text" class="form-control" value="{{ $menu->location }}" disabled>
                    </div>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Add Item Modal -->
<div class="modal fade" id="addItemModal" tabindex="-1" aria-labelledby="addItemModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('admin.menus.items.store', $menu) }}" method="POST" id="addItemForm">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="addItemModalLabel">Add Menu Item</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    @include('admin.menus._form', ['menuItem' => null, 'menu' => $menu])
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Item</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    var tree = document.getElementById('menuTree');
    if (!tree) return;

    new Sortable(tree, {
        handle: '.sort-handle',
        animation: 150,
        ghostClass: 'bg-light',
        onEnd: function() {
            saveOrder();
        }
    });

    // Make nested lists sortable too
    tree.querySelectorAll('.list-group').forEach(function(nestedList) {
        new Sortable(nestedList, {
            group: 'menu-items',
            handle: '.sort-handle',
            animation: 150,
            ghostClass: 'bg-light',
            onEnd: function() {
                saveOrder();
            }
        });
    });

    function saveOrder() {
        var items = [];
        var tree = document.getElementById('menuTree');
        var topItems = tree.querySelectorAll(':scope > .list-group-item');

        topItems.forEach(function(el, index) {
            var id = el.dataset.itemId;
            items.push({ id: id, sort_order: index, parent_id: null });

            var children = el.querySelectorAll(':scope > .list-group > .list-group-item');
            children.forEach(function(child, childIndex) {
                items.push({
                    id: child.dataset.itemId,
                    sort_order: childIndex,
                    parent_id: id
                });
            });
        });

        var token = document.querySelector('meta[name="csrf-token"]').content;

        fetch('{{ route("admin.menus.order", $menu) }}', {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': token,
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ items: items })
        }).then(function(response) {
            if (response.ok) {
                var toast = document.createElement('div');
                toast.className = 'alert alert-success alert-dismissible fade show position-fixed top-0 end-0 m-3';
                toast.style.zIndex = '9999';
                toast.innerHTML = 'Menu order saved.<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
                document.body.appendChild(toast);
                setTimeout(function() { toast.remove(); }, 3000);
            }
        });
    }
});
</script>
@endpush
