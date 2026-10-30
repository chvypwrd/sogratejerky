<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@hasSection('title')@yield('title') | @endif{{ config('app.name') }} — Admin</title>

    @vite(['resources/scss/app.scss', 'resources/js/app.js'])

    <style>
        .admin-sidebar {
            min-height: 100vh;
            background-color: #2B2B2B;
        }
        .admin-sidebar .nav-link {
            color: #E8E4DF;
            padding: 0.6rem 1rem;
            font-size: 0.9rem;
        }
        .admin-sidebar .nav-link:hover,
        .admin-sidebar .nav-link.active {
            color: #fff;
            background-color: rgba(255,255,255,0.1);
        }
        .admin-sidebar .nav-section {
            color: #6B6560;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            padding: 1rem 1rem 0.3rem;
            font-weight: 600;
        }
        .admin-content {
            min-height: 100vh;
            background-color: #FAF8F5;
        }
        .admin-topbar {
            background-color: #fff;
            border-bottom: 1px solid #D6D1CB;
        }
        .sort-handle {
            cursor: grab;
            opacity: 0.4;
            transition: opacity 0.2s;
            font-size: 1.1rem;
        }
        .sort-handle:hover {
            opacity: 1;
        }
        .sortable-ghost {
            opacity: 0.4;
            background-color: #e9ecef;
        }
        .sortable-chosen {
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
    </style>
</head>
<body>
    <a href="#main-content" class="skip-link">Skip to main content</a>

    <div class="d-flex">
        {{-- Sidebar --}}
        <nav class="admin-sidebar d-none d-md-flex flex-column flex-shrink-0 p-3" style="width: 260px;" aria-label="Admin navigation">
            <a href="{{ route('admin.dashboard') }}" class="d-flex align-items-center mb-3 text-white text-decoration-none fw-bold fs-5">
                {{ config('app.name') }}
            </a>
            <hr class="text-secondary">
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}">
                        Dashboard
                    </a>
                </li>

                <li class="nav-section">Content</li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('admin.pages.*') ? 'active' : '' }}" href="{{ route('admin.pages.index') }}">
                        Pages
                    </a>
                </li>
                @can('classes.view')
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('admin.classes.*') ? 'active' : '' }}" href="{{ route('admin.classes.index') }}">
                        Classes
                    </a>
                </li>
                @endcan
                @can('instructors.view')
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('admin.instructors.*') ? 'active' : '' }}" href="{{ route('admin.instructors.index') }}">
                        Instructors
                    </a>
                </li>
                @endcan
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

                <li class="nav-section">Appearance</li>
                @can('menus.view')
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('admin.menus.*') ? 'active' : '' }}" href="{{ route('admin.menus.index') }}">
                        Menus
                    </a>
                </li>
                @endcan
                <li class="nav-item"><span class="nav-link text-muted">Media</span></li>

                <li class="nav-section">Communications</li>
                @can('contacts.view')
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('admin.contact-messages.*') ? 'active' : '' }}" href="{{ route('admin.contact-messages.index') }}">
                        Contact Messages
                    </a>
                </li>
                @endcan

                <li class="nav-section">Administration</li>
                <li class="nav-item"><span class="nav-link text-muted">Users</span></li>
                <li class="nav-item"><span class="nav-link text-muted">Roles</span></li>
                @can('settings.view')
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('admin.settings.*') ? 'active' : '' }}" href="{{ route('admin.settings.index') }}">
                        Settings
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

                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('admin.security') ? 'active' : '' }}" href="{{ route('admin.security') }}">
                        Security
                    </a>
                </li>
            </ul>

            <div class="mt-auto pt-3 border-top border-secondary">
                <a href="{{ route('home') }}" class="nav-link" target="_blank">Visit Site</a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="nav-link btn btn-link text-start w-100">Logout</button>
                </form>
            </div>
        </nav>

        {{-- Mobile nav --}}
        <div class="d-md-none fixed-top admin-topbar p-2">
            <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileSidebar" aria-controls="mobileSidebar" aria-label="Toggle navigation">
                ☰ Menu
            </button>
            <span class="ms-2 fw-bold">{{ config('app.name') }}</span>
        </div>

        <div class="offcanvas offcanvas-start d-md-none" tabindex="-1" id="mobileSidebar" aria-labelledby="mobileSidebarLabel">
            <div class="offcanvas-header">
                <h5 class="offcanvas-title" id="mobileSidebarLabel">Navigation</h5>
                <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
            </div>
            <div class="offcanvas-body admin-sidebar">
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('admin.security') ? 'active' : '' }}" href="{{ route('admin.security') }}">Security</a>
                    </li>
                    <li class="nav-item mt-3">
                        <a href="{{ route('home') }}" class="nav-link" target="_blank">Visit Site</a>
                    </li>
                    <li class="nav-item">
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="nav-link btn btn-link text-start">Logout</button>
                        </form>
                    </li>
                </ul>
            </div>
        </div>

        {{-- Main content --}}
        <div class="admin-content flex-grow-1">
            <div class="admin-topbar d-none d-md-flex justify-content-between align-items-center px-4 py-2">
                <div></div>
                <div class="d-flex align-items-center">
                    <span class="me-3">{{ Auth::user()->name }}</span>
                </div>
            </div>

            <div class="p-4" id="main-content">
                @if (session('status'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        {{ session('status') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                @if (session('error'))
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        {{ session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                @yield('content')
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.6/Sortable.min.js"></script>
    @stack('scripts')
</body>
</html>
