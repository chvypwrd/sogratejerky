<ul class="nav flex-column">
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}">
            <i class="bi bi-speedometer2 me-2" aria-hidden="true"></i> Dashboard
        </a>
    </li>

    @can('orders.view')
    <li class="nav-section"><i class="bi bi-bag-check me-2" aria-hidden="true"></i> Orders</li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.orders.*') ? 'active' : '' }}" href="{{ route('admin.orders.index') }}">
            All Orders
        </a>
    </li>
    @endcan

    @can('contacts.view')
    <li class="nav-section">
        <i class="bi bi-envelope me-2" aria-hidden="true"></i> Messages
        @if($unreadMessageCount > 0)
            <span class="badge rounded-pill bg-danger ms-1">{{ $unreadMessageCount }}</span>
        @endif
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.contact-messages.*') ? 'active' : '' }}" href="{{ route('admin.contact-messages.index') }}">
            Contact Messages
        </a>
    </li>
    @endcan

    @canany(['products.view', 'products.categories.manage', 'products.tags.manage'])
    <li class="nav-section"><i class="bi bi-box-seam me-2" aria-hidden="true"></i> Products</li>
    @can('products.view')
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.products.*') ? 'active' : '' }}" href="{{ route('admin.products.index') }}">
            All Products
        </a>
    </li>
    @endcan
    @can('products.categories.manage')
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.product-categories.*') ? 'active' : '' }}" href="{{ route('admin.product-categories.index') }}">
            Categories
        </a>
    </li>
    @endcan
    @can('products.tags.manage')
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.product-tags.*') ? 'active' : '' }}" href="{{ route('admin.product-tags.index') }}">
            Tags
        </a>
    </li>
    @endcan
    @endcanany

    @canany(['blog.posts.view', 'blog.categories.manage', 'blog.tags.manage', 'blog.comments.moderate'])
    <li class="nav-section"><i class="bi bi-journal-text me-2" aria-hidden="true"></i> Blog</li>
    @can('blog.posts.view')
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.blog.posts.*') ? 'active' : '' }}" href="{{ route('admin.blog.posts.index') }}">
            Posts
        </a>
    </li>
    @endcan
    @can('blog.categories.manage')
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.blog.categories.*') ? 'active' : '' }}" href="{{ route('admin.blog.categories.index') }}">
            Categories
        </a>
    </li>
    @endcan
    @can('blog.tags.manage')
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.blog.tags.*') ? 'active' : '' }}" href="{{ route('admin.blog.tags.index') }}">
            Tags
        </a>
    </li>
    @endcan
    @can('blog.comments.moderate')
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.blog.comments.*') ? 'active' : '' }}" href="{{ route('admin.blog.comments.index') }}">
            Comments
        </a>
    </li>
    @endcan
    @endcanany

    @canany(['pages.view', 'testimonials.view', 'faqs.view', 'galleries.view', 'menus.view'])
    <li class="nav-section"><i class="bi bi-layout-text-window me-2" aria-hidden="true"></i> Content</li>
    @can('pages.view')
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.pages.*') ? 'active' : '' }}" href="{{ route('admin.pages.index') }}">
            Pages
        </a>
    </li>
    @endcan
    @can('menus.view')
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.menus.*') ? 'active' : '' }}" href="{{ route('admin.menus.index') }}">
            Menus
        </a>
    </li>
    @endcan
    <li class="nav-item"><span class="nav-link">Media</span></li>
    @can('testimonials.view')
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.testimonials.*') ? 'active' : '' }}" href="{{ route('admin.testimonials.index') }}">
            Testimonials
        </a>
    </li>
    @endcan
    @can('faqs.view')
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.faqs.*') ? 'active' : '' }}" href="{{ route('admin.faqs.index') }}">
            FAQs
        </a>
    </li>
    @endcan
    @can('galleries.view')
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.galleries.*') ? 'active' : '' }}" href="{{ route('admin.galleries.index') }}">
            Galleries
        </a>
    </li>
    @endcan
    @endcanany

    @can('fulfillment.view')
    <li class="nav-section"><i class="bi bi-sliders me-2" aria-hidden="true"></i> Configuration</li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.fulfillment.*') ? 'active' : '' }}" href="{{ route('admin.fulfillment.index') }}">
            Fulfillment
        </a>
    </li>
    @endcan

    @canany(['settings.view', 'redirects.view'])
    <li class="nav-section"><i class="bi bi-gear me-2" aria-hidden="true"></i> Settings</li>
    @can('settings.view')
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.settings.*') ? 'active' : '' }}" href="{{ route('admin.settings.index') }}">
            Site Settings
        </a>
    </li>
    @endcan
    @can('redirects.view')
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.redirects.*') ? 'active' : '' }}" href="{{ route('admin.redirects.index') }}">
            Redirects
        </a>
    </li>
    @endcan
    @endcanany

    <li class="nav-item"><span class="nav-link"><i class="bi bi-people me-2" aria-hidden="true"></i>Users</span></li>
    <li class="nav-item"><span class="nav-link"><i class="bi bi-shield-lock me-2" aria-hidden="true"></i>Roles</span></li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.security') ? 'active' : '' }}" href="{{ route('admin.security') }}">
            <i class="bi bi-shield-check me-2" aria-hidden="true"></i> Security
        </a>
    </li>
</ul>
