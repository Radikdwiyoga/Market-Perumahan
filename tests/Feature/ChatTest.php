<?php

namespace Tests\Feature;

use App\Models\ChatConversation;
use App\Models\SellerProfile;
use App\Models\User;
use App\Support\WhatsApp;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChatTest extends TestCase
{
    use RefreshDatabase;

    public function test_buyer_can_start_conversation_with_an_open_store(): void
    {
        $buyer = User::factory()->create();
        $store = $this->store();

        $this->actingAs($buyer)
            ->post(route('chat.start'), ['seller_profile_id' => $store->id])
            ->assertRedirect(route('chat.show', ChatConversation::first()));

        $this->assertDatabaseHas('chat_conversations', [
            'buyer_id' => $buyer->id,
            'seller_profile_id' => $store->id,
        ]);

        $this->assertDatabaseHas('user_notifications', [
            'user_id' => $store->user_id,
            'type' => 'chat_new',
        ]);
    }

    public function test_buyer_cannot_start_chat_with_closed_store(): void
    {
        $buyer = User::factory()->create();
        $store = $this->store(['status' => 'closed']);

        $this->actingAs($buyer)
            ->post(route('chat.start'), ['seller_profile_id' => $store->id])
            ->assertSessionHasErrors('seller_profile_id');

        $this->assertDatabaseCount('chat_conversations', 0);
    }

    public function test_buyer_and_seller_can_exchange_messages_and_receiver_gets_notified(): void
    {
        $buyer = User::factory()->create();
        $store = $this->store();
        $conversation = ChatConversation::create([
            'buyer_id' => $buyer->id,
            'seller_profile_id' => $store->id,
        ]);

        $this->actingAs($buyer)
            ->post(route('chat.messages.store', $conversation), ['body' => 'Halo, stoknya masih ada?'])
            ->assertRedirect(route('chat.show', $conversation));

        $this->assertDatabaseHas('chat_messages', [
            'conversation_id' => $conversation->id,
            'sender_id' => $buyer->id,
            'body' => 'Halo, stoknya masih ada?',
        ]);
        $this->assertNotNull($conversation->fresh()->last_message_at);
        $this->assertDatabaseHas('user_notifications', [
            'user_id' => $store->user_id,
            'type' => 'chat_message',
        ]);

        $this->actingAs($store->user)
            ->post(route('chat.messages.store', $conversation), ['body' => 'Masih ada, silakan dipesan.'])
            ->assertRedirect(route('chat.show', $conversation));

        $this->assertDatabaseHas('chat_messages', [
            'conversation_id' => $conversation->id,
            'sender_id' => $store->user_id,
            'body' => 'Masih ada, silakan dipesan.',
        ]);
        $this->assertDatabaseHas('user_notifications', [
            'user_id' => $buyer->id,
            'type' => 'chat_message',
        ]);
    }

    public function test_reading_the_thread_marks_messages_as_read(): void
    {
        $buyer = User::factory()->create();
        $store = $this->store();
        $conversation = ChatConversation::create([
            'buyer_id' => $buyer->id,
            'seller_profile_id' => $store->id,
        ]);
        $message = $conversation->messages()->create([
            'sender_id' => $buyer->id,
            'body' => 'Pesan uji',
        ]);

        $this->actingAs($store->user)->get(route('chat.show', $conversation))->assertOk();

        $this->assertNotNull($message->fresh()->read_at);
    }

    public function test_strangers_cannot_access_an_existing_conversation(): void
    {
        $buyer = User::factory()->create();
        $store = $this->store();
        $stranger = User::factory()->create();
        $conversation = ChatConversation::create([
            'buyer_id' => $buyer->id,
            'seller_profile_id' => $store->id,
        ]);

        $this->actingAs($stranger)->get(route('chat.show', $conversation))->assertNotFound();
        $this->actingAs($stranger)->post(route('chat.messages.store', $conversation), ['body' => 'Halo'])->assertNotFound();
    }

    public function test_admin_cannot_access_chat(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->get(route('chat.index'))->assertForbidden();
    }

    public function test_whatsapp_helper_normalizes_phone_and_builds_wa_link(): void
    {
        $this->assertSame('6281234567890', WhatsApp::normalize('081234567890'));
        $this->assertSame('6281234567890', WhatsApp::normalize('+62 812-3456-7890'));
        $this->assertSame('6281234567890', WhatsApp::normalize('81234567890'));
        $this->assertStringStartsWith('https://wa.me/6281234567890?text=', WhatsApp::chatLink('081234567890', 'Halo toko'));
        $this->assertStringContainsString('Halo%20toko', WhatsApp::chatLink('081234567890', 'Halo toko'));
    }

    private function store(array $attributes = []): SellerProfile
    {
        $seller = User::factory()->create(['role' => 'seller']);

        return SellerProfile::create(array_merge([
            'user_id' => $seller->id,
            'store_name' => 'Warung Warga',
            'phone' => $seller->phone,
            'address' => 'Blok A1',
            'status' => 'open',
        ], $attributes));
    }
}
