@extends('layouts.app')

@section('title', 'Edit Address')

@section('meta')
    <meta name="robots" content="noindex,follow">
@endsection

@section('content')
<section class="py-5">
    <div class="container">
        <header class="page-header">
            <span class="eyebrow">Account</span>
            <h1>Edit Address</h1>
        </header>

        @include('account._nav')

        <form action="{{ route('account.addresses.update', $address) }}" method="POST">
            @csrf
            @method('PUT')
            @include('account.addresses._form', ['address' => $address])
        </form>
    </div>
</section>
@endsection
