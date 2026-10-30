@extends('admin.layouts.app')

@section('title', 'Contact Messages')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Contact Messages</h1>
</div>

<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('admin.contact-messages.index') }}" class="row g-3">
            <div class="col-md-6">
                <div class="input-group">
                    <input type="text" class="form-control" name="search" placeholder="Search messages..." value="{{ $search ?? '' }}">
                    <button class="btn btn-outline-secondary" type="submit">Search</button>
                </div>
            </div>
            <div class="col-md-4">
                <select class="form-select" name="filter" onchange="this.form.submit()">
                    <option value="inbox" {{ ($filter ?? 'inbox') === 'inbox' ? 'selected' : '' }}>Inbox</option>
                    <option value="new" {{ ($filter ?? '') === 'new' ? 'selected' : '' }}>New</option>
                    <option value="read" {{ ($filter ?? '') === 'read' ? 'selected' : '' }}>Read</option>
                    <option value="replied" {{ ($filter ?? '') === 'replied' ? 'selected' : '' }}>Replied</option>
                    <option value="archived" {{ ($filter ?? '') === 'archived' ? 'selected' : '' }}>Archived</option>
                    <option value="spam" {{ ($filter ?? '') === 'spam' ? 'selected' : '' }}>Spam</option>
                    <option value="all" {{ ($filter ?? '') === 'all' ? 'selected' : '' }}>All</option>
                </select>
            </div>
            <div class="col-md-2">
                <a href="{{ route('admin.contact-messages.index') }}" class="btn btn-outline-secondary w-100">Clear</a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>From</th>
                    <th>Subject</th>
                    <th>Date</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($messages as $message)
                    <tr class="{{ $message->status->value === 'new' ? 'table-light fw-bold' : '' }}">
                        <td>
                            <a href="{{ route('admin.contact-messages.show', $message) }}" class="text-decoration-none">
                                {{ $message->name }}
                                <br><small class="text-muted">{{ $message->email }}</small>
                            </a>
                        </td>
                        <td>
                            <a href="{{ route('admin.contact-messages.show', $message) }}" class="text-decoration-none text-dark">
                                {{ $message->subject ?: '(No subject)' }}
                            </a>
                        </td>
                        <td class="text-nowrap">{{ $message->created_at->format('M j, Y g:i A') }}</td>
                        <td>
                            <span class="badge bg-{{ $message->status->color() }}">
                                {{ $message->status->label() }}
                            </span>
                        </td>
                        <td class="text-end text-nowrap">
                            <a href="{{ route('admin.contact-messages.show', $message) }}" class="btn btn-sm btn-outline-primary">View</a>

                            @can('update', $message)
                                @if ($message->status->value !== 'read')
                                    <form method="POST" action="{{ route('admin.contact-messages.mark-read', $message) }}" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline-info" title="Mark read">Read</button>
                                    </form>
                                @endif

                                @if ($message->status->value === 'read' || $message->status->value === 'replied')
                                    <form method="POST" action="{{ route('admin.contact-messages.mark-unread', $message) }}" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline-warning" title="Mark unread">Unread</button>
                                    </form>
                                @endif

                                @if ($message->status->value !== 'replied' && $message->status->value !== 'archived' && $message->status->value !== 'spam')
                                    <form method="POST" action="{{ route('admin.contact-messages.mark-replied', $message) }}" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline-success" title="Mark replied">Replied</button>
                                    </form>
                                @endif

                                @if ($message->status->value !== 'archived' && $message->status->value !== 'spam')
                                    <form method="POST" action="{{ route('admin.contact-messages.archive', $message) }}" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline-secondary" title="Archive">Archive</button>
                                    </form>
                                @endif

                                @if ($message->status->value !== 'spam')
                                    <form method="POST" action="{{ route('admin.contact-messages.spam', $message) }}" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Mark spam">Spam</button>
                                    </form>
                                @endif

                                @if ($message->status->value === 'spam' || $message->status->value === 'archived')
                                    <form method="POST" action="{{ route('admin.contact-messages.restore', $message) }}" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline-success" title="Restore to inbox">Restore</button>
                                    </form>
                                @endif
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center text-muted py-4">No messages found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-3">
    {{ $messages->links() }}
</div>
@endsection
