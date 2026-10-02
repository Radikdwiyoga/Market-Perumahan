<?php

namespace Tests\Feature;

use App\Models\Complaint;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ComplaintTest extends TestCase
{
    use RefreshDatabase;

    public function test_buyer_can_submit_a_complaint_for_their_order(): void
    {
        Storage::fake('public');
        $buyer = User::factory()->create();
        $order = Order::create(['order_number' => 'ORD-COMPLAINT-001', 'buyer_id' => $buyer->id, 'subtotal' => 20000, 'total_amount' => 20000, 'status' => 'completed']);

        $response = $this->actingAs($buyer)->post(route('complaints.store', $order), [
            'category' => 'damaged_item',
            'description' => 'Barang datang dalam kondisi rusak.',
            'evidence_image' => UploadedFile::fake()->image('damage.jpg'),
        ]);

        $response->assertRedirect(route('orders.show', $order));
        $complaint = Complaint::first();
        $this->assertSame('open', $complaint->status);
        Storage::disk('public')->assertExists($complaint->evidence_image);
    }

    public function test_admin_can_update_complaint_status(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $buyer = User::factory()->create();
        $order = Order::create(['order_number' => 'ORD-COMPLAINT-002', 'buyer_id' => $buyer->id, 'subtotal' => 20000, 'total_amount' => 20000]);
        $complaint = Complaint::create(['order_id' => $order->id, 'buyer_id' => $buyer->id, 'category' => 'payment', 'description' => 'Pembayaran perlu dicek.']);

        $this->actingAs($admin)->get(route('admin.complaints.index'))->assertOk()->assertSee('ORD-COMPLAINT-002');
        $this->actingAs($admin)->patch(route('admin.complaints.status', $complaint), ['status' => 'in_review'])->assertRedirect();

        $this->assertDatabaseHas('complaints', ['id' => $complaint->id, 'status' => 'in_review']);
    }

    public function test_admin_can_see_complaints_with_photo_evidence(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['role' => 'admin']);
        $buyer = User::factory()->create();
        $order = Order::create(['order_number' => 'ORD-EVIDENCE-001', 'buyer_id' => $buyer->id, 'subtotal' => 20000, 'total_amount' => 20000]);
        Complaint::create(['order_id' => $order->id, 'buyer_id' => $buyer->id, 'category' => 'damaged_item', 'description' => 'Barang penyok.', 'evidence_image' => 'complaints/evidence.png']);

        $this->actingAs($admin)->get(route('admin.complaints.index'))
            ->assertOk()
            ->assertSee('ORD-EVIDENCE-001')
            ->assertSee('complaints/evidence.png');
    }

    public function test_admin_can_filter_complaints_by_status(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $buyer = User::factory()->create();
        $openOrder = Order::create(['order_number' => 'ORD-FILTER-OPEN', 'buyer_id' => $buyer->id, 'subtotal' => 20000, 'total_amount' => 20000]);
        $resolvedOrder = Order::create(['order_number' => 'ORD-FILTER-DONE', 'buyer_id' => $buyer->id, 'subtotal' => 20000, 'total_amount' => 20000]);
        Complaint::create(['order_id' => $openOrder->id, 'buyer_id' => $buyer->id, 'category' => 'other', 'description' => 'Masih dibuka.']);
        Complaint::create(['order_id' => $resolvedOrder->id, 'buyer_id' => $buyer->id, 'category' => 'other', 'description' => 'Sudah beres.', 'status' => 'resolved']);

        $this->actingAs($admin)->get(route('admin.complaints.index'))
            ->assertOk()
            ->assertSee('ORD-FILTER-OPEN')
            ->assertSee('ORD-FILTER-DONE');

        $this->actingAs($admin)->get(route('admin.complaints.index', ['status' => 'resolved']))
            ->assertOk()
            ->assertSee('ORD-FILTER-DONE')
            ->assertDontSee('ORD-FILTER-OPEN');
    }

    public function test_buyer_cannot_submit_complaint_for_another_buyers_order(): void
    {
        $buyer = User::factory()->create();
        $otherBuyer = User::factory()->create();
        $order = Order::create(['order_number' => 'ORD-COMPLAINT-003', 'buyer_id' => $otherBuyer->id, 'subtotal' => 20000, 'total_amount' => 20000]);

        $this->actingAs($buyer)->get(route('complaints.create', $order))->assertForbidden();
    }
}
