<?php

namespace App\Support;

use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\SellerOrder;
use App\Models\SellerProfile;
use App\Models\User;
use App\Models\UserNotification;
use App\Services\WhatsappService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OrderService
{
    public function __construct(
        protected CartService $carts,
        protected WhatsappService $whatsapp,
    ) {}

    /**
     * Buat order dari keranjang milik pembeli, beserta sub-order, item,
     * pembayaran, dan pengiriman dalam satu transaksi.
     *
     * @param  array{shipping_methods: array<int|string, string>, shipping_address?: ?string, shipping_note?: ?string, payment_method: string}  $validated
     */
    public function createOrder(User $buyer, array $validated): Order
    {
        $cart = $this->carts->quantities($buyer);
        $notes = $this->carts->notes($buyer);

        abort_if($cart === [], 422, 'Keranjang masih kosong.');

        $order = DB::transaction(function () use ($buyer, $cart, $notes, $validated): Order {
            $products = Product::query()
                ->with('sellerProfile.paymentSetting')
                ->whereIn('id', array_keys($cart))
                ->lockForUpdate()
                ->get();

            abort_if($products->count() !== count($cart), 422, 'Ada produk di keranjang yang sudah tidak tersedia.');

            foreach ($products as $product) {
                abort_if($product->status !== 'active' || $product->sellerProfile->status !== 'open', 422, "Produk {$product->name} tidak tersedia.");
                abort_if($cart[$product->id] > $product->stock, 422, "Stok {$product->name} tidak mencukupi.");
            }

            $groups = $this->sellerGroups($products, $cart);
            $methods = $this->resolveShippingMethods($groups, $validated['shipping_methods']);
            $hasDelivery = in_array('seller_delivery', $methods, true);
            $address = trim((string) ($validated['shipping_address'] ?? ''));

            abort_if($hasDelivery && $address === '', 422, 'Alamat pengiriman wajib diisi untuk pesanan yang diantar.');

            $this->assertPaymentMethodSupported($groups, $validated['payment_method']);

            $subtotal = $this->subtotal($products, $cart);
            $shippingFee = $this->estimateShippingFee($groups, $methods);
            $order = Order::create([
                'order_number' => 'ORD-'.now()->format('Ymd').'-'.Str::upper(Str::random(6)),
                'buyer_id' => $buyer->id,
                'subtotal' => $subtotal,
                'shipping_fee' => $shippingFee,
                'total_amount' => $subtotal + $shippingFee,
                'status' => 'pending',
            ]);

            foreach ($groups as $sellerProfileId => $group) {
                $seller = $group['store'];
                $method = $methods[$sellerProfileId];
                $sellerShippingFee = $seller->shippingFeeFor($method, $group['subtotal']);
                $sellerOrder = SellerOrder::create([
                    'order_id' => $order->id,
                    'seller_profile_id' => $seller->id,
                    'subtotal' => $group['subtotal'],
                    'shipping_fee' => $sellerShippingFee,
                    'total_amount' => $group['subtotal'] + $sellerShippingFee,
                    'payment_status' => 'pending',
                    'shipping_method' => $method,
                    'shipping_status' => 'pending',
                    'pickup_code' => $method === 'store_pickup' ? strtoupper(Str::random(6)) : null,
                    'payment_due_at' => $validated['payment_method'] === 'cod'
                        ? null
                        : now()->addMinutes((int) config('marketplace.payment_expiry_minutes', 15)),
                ]);

                foreach ($group['products'] as $product) {
                    $quantity = $cart[$product->id];
                    $order->items()->create([
                        'seller_profile_id' => $seller->id,
                        'product_id' => $product->id,
                        'product_name' => $product->name,
                        'price' => $product->effectivePrice(),
                        'quantity' => $quantity,
                        'note' => $notes[$product->id] ?? null,
                        'subtotal' => $product->effectivePrice() * $quantity,
                    ]);
                    $product->decrement('stock', $quantity);
                }

                $sellerOrder->payments()->create([
                    'order_id' => $order->id,
                    'buyer_id' => $buyer->id,
                    'seller_profile_id' => $seller->id,
                    'method' => $validated['payment_method'],
                    'amount' => $group['subtotal'] + $sellerShippingFee,
                    // Disimpan saat checkout agar riwayat tetap menampilkan QRIS
                    // yang benar walau seller mengganti gambarnya nanti.
                    'qris_image_snapshot' => $validated['payment_method'] === 'qris'
                        ? $seller->paymentSetting?->qris_image
                        : null,
                ]);

                $sellerOrder->shipment()->create([
                    'method' => $method,
                    'address' => $method === 'seller_delivery' ? $address : $seller->address,
                    'recipient_name' => $buyer->name,
                    'recipient_phone' => $buyer->phone,
                    'shipping_fee' => $sellerShippingFee,
                ]);
            }

            return $order;
        });

        $this->carts->clear($buyer);

        $order->load('sellerOrders.sellerProfile.user');
        AuditLogger::log('ORDER_CREATED', 'Order', $order->id, [
            'order_number' => $order->order_number,
            'total' => $order->total_amount,
            'shipping_fee' => $order->shipping_fee,
            'seller_count' => $order->sellerOrders->count(),
        ]);

        $dueInfo = $order->sellerOrders->first()->payment_due_at
            ? ' Silakan selesaikan pembayaran sebelum '.$order->sellerOrders->first()->payment_due_at->format('d M Y H:i').'.'
            : '';
        UserNotification::send($order->buyer_id, 'Pesanan dibuat', "Pesanan {$order->order_number} berhasil dibuat.{$dueInfo}", 'order_created');

        foreach ($order->sellerOrders as $sellerOrder) {
            UserNotification::send(
                $sellerOrder->sellerProfile->user_id,
                'Order baru',
                "Pesanan baru {$order->order_number} senilai Rp".number_format($sellerOrder->total_amount, 0, ',', '.').' masuk ke toko Anda.',
                'new_order'
            );

            // Kirim notifikasi WhatsApp ke nomor penjual (dari profil toko atau akun user).
            $sellerPhone = $sellerOrder->sellerProfile->phone
                ?: $sellerOrder->sellerProfile->user?->phone;

            if ($sellerPhone) {
                $paymentLabel = match ($validated['payment_method']) {
                    'cod' => 'COD (bayar di tempat)',
                    'qris' => 'QRIS',
                    'bank_transfer' => 'Transfer Bank',
                    default => $validated['payment_method'],
                };

                $this->whatsapp->send(
                    $sellerPhone,
                    "🛒 *Pesanan Baru Masuk!*\n\n".
                    "Nomor: *{$order->order_number}*\n".
                    "Pembeli: {$buyer->name}\n".
                    'Total: Rp'.number_format($sellerOrder->total_amount, 0, ',', '.')."\n".
                    "Pembayaran: {$paymentLabel}\n\n".
                    'Segera konfirmasi pesanan di dashboard penjual.'."\n\n".
                    '👇 Buka daftar pesanan masuk:'."\n".
                    route('seller.orders.index'),
                );
            }
        }

        return $order;
    }

    /**
     * Pastikan setiap toko mendukung metode pembayaran yang dipilih.
     *
     * QRIS hanya bisa dipakai bila toko sudah mengunggah gambar QRIS-nya.
     *
     * @param  Collection<int, array{store: SellerProfile, products: Collection<int, Product>, subtotal: int, methods: list<array{value: string, label: string, fee: int}>}>  $groups
     */
    private function assertPaymentMethodSupported(Collection $groups, string $paymentMethod): void
    {
        if ($paymentMethod !== Payment::METHOD_QRIS) {
            return;
        }

        foreach ($groups as $group) {
            $store = $group['store'];
            abort_if(
                $store->paymentSetting?->qris_image === null,
                422,
                "Toko {$store->store_name} belum mengunggah QRIS. Pilih transfer bank atau COD.",
            );
        }
    }

    /**
     * Kelompokkan produk per toko lengkap dengan subtotal dan opsi pengiriman.
     *
     * @param  array<int, int>  $quantities
     * @return Collection<int, array{store: SellerProfile, products: Collection<int, Product>, subtotal: int, methods: list<array{value: string, label: string, fee: int}>}>
     */
    private function sellerGroups(Collection $products, array $quantities): Collection
    {
        return $products->groupBy('seller_profile_id')->map(function (Collection $sellerProducts) use ($quantities): array {
            $store = $sellerProducts->first()->sellerProfile;
            $subtotal = $sellerProducts->sum(fn (Product $product): int => $product->effectivePrice() * $quantities[$product->id]);

            return [
                'store' => $store,
                'products' => $sellerProducts,
                'subtotal' => $subtotal,
                'methods' => collect($store->availableShippingMethods())->map(fn (string $method): array => [
                    'value' => $method,
                    'label' => $method === 'seller_delivery' ? 'Diantar penjual' : 'Ambil di toko',
                    'fee' => $store->shippingFeeFor($method, $subtotal),
                ])->values()->all(),
            ];
        });
    }

    /**
     * @param  array<int|string, array<string, mixed>>  $groups
     * @param  array<int|string, string>  $selected
     * @return array<int, string>
     */
    private function resolveShippingMethods(Collection $groups, array $selected): array
    {
        $methods = [];

        foreach ($groups as $sellerProfileId => $group) {
            $method = $selected[$sellerProfileId] ?? null;

            abort_if($method === null, 422, "Metode pengiriman untuk {$group['store']->store_name} belum dipilih.");
            abort_if(! $group['store']->isShippingMethodAvailable($method), 422, "Metode pengiriman {$group['store']->store_name} tidak tersedia.");
            abort_if($method === 'seller_delivery' && ! $group['store']->meetsMinimumOrder($group['subtotal']), 422, "Minimal pembelian di {$group['store']->store_name} belum terpenuhi untuk pengiriman diantar.");

            $methods[$sellerProfileId] = $method;
        }

        return $methods;
    }

    /**
     * @param  array<int, int>  $quantities
     */
    private function subtotal(Collection $products, array $quantities): int
    {
        return (int) $products->sum(fn (Product $product): int => $product->effectivePrice() * $quantities[$product->id]);
    }

    /**
     * @param  array<int|string, array<string, mixed>>  $groups
     * @param  array<int, string>|null  $methods
     */
    private function estimateShippingFee(Collection $groups, ?array $methods = null): int
    {
        $fee = 0;

        foreach ($groups as $sellerProfileId => $group) {
            $method = $methods[$sellerProfileId] ?? $group['methods'][0]['value'] ?? null;

            if ($method !== null) {
                $fee += $group['store']->shippingFeeFor($method, $group['subtotal']);
            }
        }

        return $fee;
    }
}
