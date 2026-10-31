<nav class="account-nav" aria-label="Account navigation">
    <a href="{{ route('account.dashboard') }}" class="{{ request()->routeIs('account.dashboard') ? 'active' : '' }}">Dashboard</a>
    <a href="{{ route('account.profile.edit') }}" class="{{ request()->routeIs('account.profile.*') ? 'active' : '' }}">Profile</a>
    <a href="{{ route('account.addresses.index') }}" class="{{ request()->routeIs('account.addresses.*') ? 'active' : '' }}">Addresses</a>
    <a href="{{ route('account.orders.index') }}" class="{{ request()->routeIs('account.orders.*') ? 'active' : '' }}">Orders</a>
    <a href="{{ route('account.payment-methods.index') }}" class="{{ request()->routeIs('account.payment-methods.*') ? 'active' : '' }}">Payment Methods</a>
    <a href="{{ route('cart.index') }}">Cart</a>
    <form method="POST" action="{{ route('customer.logout') }}" class="account-nav__logout">
        @csrf
        <button type="submit">Sign Out</button>
    </form>
</nav>
