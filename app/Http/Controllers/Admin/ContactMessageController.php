<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ContactMessageStatus;
use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ContactMessageController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', ContactMessage::class);

        $filter = $request->get('filter', 'inbox');
        $search = $request->get('search');

        $query = match ($filter) {
            'new' => ContactMessage::newMessages(),
            'read' => ContactMessage::where('status', ContactMessageStatus::Read),
            'replied' => ContactMessage::where('status', ContactMessageStatus::Replied),
            'archived' => ContactMessage::where('status', ContactMessageStatus::Archived),
            'spam' => ContactMessage::where('status', ContactMessageStatus::Spam),
            'all' => ContactMessage::query(),
            default => ContactMessage::inbox(),
        };

        if ($search) {
            $query->search($search);
        }

        $messages = $query->latest()->paginate(20)->withQueryString();

        return view('admin.contact-messages.index', compact('messages', 'filter', 'search'));
    }

    public function show(ContactMessage $contactMessage): View
    {
        $this->authorize('view', $contactMessage);

        if ($contactMessage->status === ContactMessageStatus::New) {
            $contactMessage->markRead();
        }

        return view('admin.contact-messages.show', ['message' => $contactMessage]);
    }

    public function markRead(ContactMessage $contactMessage): RedirectResponse
    {
        $this->authorize('update', $contactMessage);
        $contactMessage->markRead();

        return redirect()->back()->with('status', 'Message marked as read.');
    }

    public function markUnread(ContactMessage $contactMessage): RedirectResponse
    {
        $this->authorize('update', $contactMessage);
        $contactMessage->markUnread();

        return redirect()->back()->with('status', 'Message marked as unread.');
    }

    public function markReplied(ContactMessage $contactMessage): RedirectResponse
    {
        $this->authorize('update', $contactMessage);
        $contactMessage->markReplied();

        return redirect()->back()->with('status', 'Message marked as replied.');
    }

    public function archive(ContactMessage $contactMessage): RedirectResponse
    {
        $this->authorize('update', $contactMessage);
        $contactMessage->archive();

        return redirect()->back()->with('status', 'Message archived.');
    }

    public function spam(ContactMessage $contactMessage): RedirectResponse
    {
        $this->authorize('update', $contactMessage);
        $contactMessage->markSpam();

        return redirect()->back()->with('status', 'Message marked as spam.');
    }

    public function restore(ContactMessage $contactMessage): RedirectResponse
    {
        $this->authorize('update', $contactMessage);
        $contactMessage->restoreToInbox();

        return redirect()->back()->with('status', 'Message restored to inbox.');
    }

    public function destroy(ContactMessage $contactMessage): RedirectResponse
    {
        $this->authorize('delete', $contactMessage);
        $contactMessage->delete();

        return redirect()->route('admin.contact-messages.index')
            ->with('status', 'Message deleted permanently.');
    }
}
