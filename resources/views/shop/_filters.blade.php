<div class="shop-filters">
    @if($sort !== null)
        <form action="{{ route('shop.index') }}" method="GET" class="shop-sort">
            <label for="shop-sort" class="form-label">Sort by</label>
            <select class="form-select" id="shop-sort" name="sort" onchange="this.form.submit()">
                <option value="featured" {{ $sort === 'featured' ? 'selected' : '' }}>Featured</option>
                <option value="name" {{ $sort === 'name' ? 'selected' : '' }}>Name</option>
                <option value="newest" {{ $sort === 'newest' ? 'selected' : '' }}>Newest</option>
            </select>
            <noscript><button type="submit" class="btn btn-sm btn-brand mt-2">Apply</button></noscript>
        </form>
    @endif

    @if($categories->isNotEmpty())
        <div class="shop-taxonomy">
            <span class="shop-taxonomy__label">Categories</span>
            <div class="shop-taxonomy__links">
                @foreach($categories as $category)
                    <a href="{{ route('shop.category', $category->slug) }}">{{ $category->name }}</a>
                @endforeach
            </div>
        </div>
    @endif

    @if($tags->isNotEmpty())
        <div class="shop-taxonomy">
            <span class="shop-taxonomy__label">Tags</span>
            <div class="shop-taxonomy__links">
                @foreach($tags as $tag)
                    <a href="{{ route('shop.tag', $tag->slug) }}">{{ $tag->name }}</a>
                @endforeach
            </div>
        </div>
    @endif
</div>
