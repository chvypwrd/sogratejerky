@if($posts->count() > 0)
    <div class="row g-4">
        @foreach($posts as $post)
            <div class="col-md-6 col-lg-4">
                @include('blog._post-card', ['post' => $post])
            </div>
        @endforeach
    </div>

    <div class="mt-5">
        {{ $posts->links() }}
    </div>
@else
    <div class="empty-state">
        <p class="mb-0">No published articles found.</p>
    </div>
@endif
