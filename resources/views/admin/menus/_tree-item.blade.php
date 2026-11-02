<li class="list-group-item d-flex align-items-center {{ $depth > 0 ? 'ms-4' : '' }}" data-item-id="{{ $item['id'] }}">
    <span class="sort-handle me-2 text-muted" style="cursor: grab; user-select: none;">&#9776;</span>
    <div class="flex-grow-1">
        <span class="fw-medium">{{ $item['label'] }}</span>
        <small class="text-muted ms-2">
            @if($item['type'] === 'page')
                Page
            @elseif($item['type'] === 'gallery')
                Gallery
            @elseif($item['type'] === 'home')
                Home
            @else
                Custom: {{ $item['url'] ?? '/' }}
            @endif
        </small>
        @if(!$item['enabled'])
            <span class="badge bg-warning text-dark ms-2">Inactive</span>
        @endif
    </div>
    <div class="btn-group btn-group-sm">
        <a href="{{ route('admin.menus.items.edit', ['menu' => $menu, 'menuItem' => $item['slug']]) }}" class="btn btn-outline-secondary btn-sm">Edit</a>
        <form action="{{ route('admin.menus.items.destroy', ['menu' => $menu, 'menuItem' => $item['slug']]) }}" method="POST" class="d-inline">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-outline-danger btn-sm" onclick="return confirm('Delete this menu item?')">Delete</button>
        </form>
    </div>
</li>
@if(!empty($item['children']))
    @foreach($item['children'] as $child)
        @include('admin.menus._tree-item', ['item' => $child, 'depth' => $depth + 1])
    @endforeach
@endif
