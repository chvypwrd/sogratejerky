@extends('admin.layouts.app')

@section('title', 'View Message')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <a href="{{ route('admin.contact-messages.index') }}" class="text-decoration-none">&larr; Back to messages</a>
        <h1 class="h3 mb-0 mt-1">View Message</h1>
    </div>
    <div class="d-flex gap-2">
        @can('update', $message)
            @if ($message->status->value !== 'read')
                <form method="POST" action="{{ route('admin.contact-messages.mark-read', $message) }}">
                    @csrf
                    <button type="submit" class="btn btn-outline-info btn-sm">Mark Read</button>
                </form>
            @endif

            @if ($message->status->value === 'read' || $message->status->value === 'replied')
                <form method="POST" action="{{ route('admin.contact-messages.mark-unread', $message) }}">
                    @csrf
                    <button type="submit" class="btn btn-outline-warning btn-sm">Mark Unread</button>
                </form>
            @endif

            @if ($message->status->value !== 'replied' && $message->status->value !== 'archived' && $message->status->value !== 'spam')
                <form method="POST" action="{{ route('admin.contact-messages.mark-replied', $message) }}">
                    @csrf
                    <button type="submit" class="btn btn-outline-success btn-sm">Mark Replied</button>
                </form>
            @endif

            @if ($message->status->value !== 'archived' && $message->status->value !== 'spam')
                <form method="POST" action="{{ route('admin.contact-messages.archive', $message) }}">
                    @csrf
                    <button type="submit" class="btn btn-outline-secondary btn-sm">Archive</button>
                </form>
            @endif

            @if ($message->status->value !== 'spam')
                <form method="POST" action="{{ route('admin.contact-messages.spam', $message) }}">
                    @csrf
                    <button type="submit" class="btn btn-outline-danger btn-sm">Spam</button>
                </form>
            @endif

            @if ($message->status->value === 'spam' || $message->status->value === 'archived')
                <form method="POST" action="{{ route('admin.contact-messages.restore', $message) }}">
                    @csrf
                    <button type="submit" class="btn btn-outline-success btn-sm">Restore</button>
                </form>
            @endif
        @endcan

        @can('delete', $message)
            <form method="POST" action="{{ route('admin.contact-messages.destroy', $message) }}" onsubmit="return confirm('Are you sure you want to permanently delete this message?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-danger btn-sm">Delete</button>
            </form>
        @endcan
    </div>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">{{ $message->subject ?: '(No subject)' }}</h5>
                <span class="badge bg-{{ $message->status->color() }}">{{ $message->status->label() }}</span>
            </div>
            <div class="card-body">
                <div class="message-body" style="white-space: pre-wrap;">{{ $message->message }}</div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">Sender Details</h6>
            </div>
            <div class="card-body">
                <dl class="mb-0">
                    <dt>Name</dt>
                    <dd>{{ $message->name }}</dd>

                    <dt>Email</dt>
                    <dd><a href="mailto:{{ $message->email }}">{{ $message->email }}</a></dd>

                    @if ($message->phone)
                        <dt>Phone</dt>
                        <dd><a href="tel:{{ $message->phone }}">{{ $message->phone }}</a></dd>
                    @endif

                    <dt>Received</dt>
                    <dd>{{ $message->created_at->format('M j, Y g:i A') }}</dd>

                    @if ($message->read_at)
                        <dt>Read</dt>
                        <dd>{{ $message->read_at->format('M j, Y g:i A') }}</dd>
                    @endif

                    @if ($message->replied_at)
                        <dt>Replied</dt>
                        <dd>{{ $message->replied_at->format('M j, Y g:i A') }}</dd>
                    @endif
                </dl>
            </div>
        </div>
    </div>
</div>
@endsection
