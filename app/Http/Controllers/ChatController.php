<?php

namespace App\Http\Controllers;

use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Models\UserNotification;
use App\Support\WhatsApp;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ChatController extends Controller
{
    public function index(): View
    {
        $this->ensureChatUser();
        $user = auth()->user();

        $conversations = ChatConversation::query()
            ->with(['buyer', 'sellerProfile.user', 'latestMessage'])
            ->when($user->isSeller(), fn ($query) => $query->where('seller_profile_id', $user->sellerProfile?->id))
            ->when($user->role === 'buyer', fn ($query) => $query->where('buyer_id', $user->id))
            ->get()
            ->sortByDesc(fn (ChatConversation $conversation) => $conversation->latestMessage?->created_at ?? $conversation->created_at);

        $unreadCounts = ChatMessage::query()
            ->whereIn('conversation_id', $conversations->pluck('id'))
            ->where('sender_id', '!=', $user->id)
            ->whereNull('read_at')
            ->selectRaw('conversation_id, COUNT(*) as total')
            ->groupBy('conversation_id')
            ->pluck('total', 'conversation_id');

        return view('chat.index', [
            'conversations' => $conversations,
            'unreadCounts' => $unreadCounts,
        ]);
    }

    public function show(int $conversation): View
    {
        $conversation = $this->findForUser($conversation)->load(['messages.sender']);
        $user = auth()->user();
        $counterpart = $conversation->counterpart($user);
        $conversation->markReadBy($user);

        return view('chat.show', [
            'conversation' => $conversation,
            'counterpart' => $counterpart,
            'whatsappLink' => WhatsApp::chatLink(
                $counterpart->phone,
                "Halo {$counterpart->name}, saya {$user->name} dari Marketplace Perumahan."
            ),
        ]);
    }

    public function store(Request $request, int $conversation): RedirectResponse
    {
        $conversation = $this->findForUser($conversation);
        $validated = $request->validate(['body' => ['required', 'string', 'max:2000']]);
        $user = auth()->user();
        $counterpart = $conversation->counterpart($user);

        $conversation->messages()->create([
            'sender_id' => $user->id,
            'body' => $validated['body'],
        ]);
        $conversation->update(['last_message_at' => now()]);

        UserNotification::send(
            $counterpart->id,
            'Pesan baru',
            "Anda mendapat pesan dari {$user->name}: \"".mb_strimwidth($validated['body'], 0, 80, '...').'"',
            'chat_message'
        );

        return redirect()->route('chat.show', $conversation);
    }

    public function start(Request $request): RedirectResponse
    {
        abort_unless(auth()->user()->role === 'buyer', 403);

        $validated = $request->validate([
            'seller_profile_id' => ['required', 'integer', Rule::exists('seller_profiles', 'id')->where(fn ($query) => $query->where('status', 'open'))],
        ]);

        $conversation = ChatConversation::firstOrCreate([
            'buyer_id' => auth()->id(),
            'seller_profile_id' => $validated['seller_profile_id'],
        ]);

        if ($conversation->wasRecentlyCreated) {
            UserNotification::send(
                $conversation->sellerProfile->user_id,
                'Chat masuk',
                'Ada pembeli yang mulai mengobrol dengan toko Anda.',
                'chat_new'
            );
        }

        return redirect()->route('chat.show', $conversation);
    }

    private function findForUser(int $conversation): ChatConversation
    {
        $user = auth()->user();
        $conversation = ChatConversation::query()->with(['buyer', 'sellerProfile.user'])->findOrFail($conversation);
        abort_unless($conversation->isParticipant($user), 404);

        return $conversation;
    }

    private function ensureChatUser(): void
    {
        abort_unless(in_array(auth()->user()->role, ['buyer', 'seller'], true), 403);
    }
}
