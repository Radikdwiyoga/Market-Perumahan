<?php

namespace App\Support;

use App\Models\CartItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class CartService
{
    /**
     * Kuantitas per produk dalam keranjang milik user (product_id => quantity).
     *
     * @return array<int, int>
     */
    public function quantities(User $user): array
    {
        return CartItem::query()
            ->where('user_id', $user->id)
            ->pluck('quantity', 'product_id')
            ->all();
    }

    /**
     * Catatan per produk dalam keranjang milik user (product_id => note).
     *
     * @return array<int, string|null>
     */
    public function notes(User $user): array
    {
        return CartItem::query()
            ->where('user_id', $user->id)
            ->pluck('note', 'product_id')
            ->all();
    }

    /**
     * @return Collection<int, Product>
     */
    public function products(User $user): Collection
    {
        $quantities = $this->quantities($user);

        if ($quantities === []) {
            return collect();
        }

        return Product::query()
            ->with('sellerProfile')
            ->whereIn('id', array_keys($quantities))
            ->get();
    }

    public function count(User $user): int
    {
        return (int) CartItem::query()->where('user_id', $user->id)->sum('quantity');
    }

    public function subtotal(User $user): int
    {
        $quantities = $this->quantities($user);

        return (int) $this->products($user)->sum(
            fn (Product $product): int => $product->effectivePrice() * ($quantities[$product->id] ?? 0)
        );
    }

    /**
     * Tambahkan produk (menambah kuantitas bila sudah ada) dengan cek stok.
     */
    public function add(User $user, Product $product, int $quantity): void
    {
        $current = CartItem::query()->where('user_id', $user->id)->where('product_id', $product->id)->value('quantity') ?? 0;
        $nextQuantity = $current + $quantity;

        if ($nextQuantity > $product->stock) {
            throw ValidationException::withMessages(['cart' => "Stok {$product->name} tidak mencukupi."]);
        }

        CartItem::query()->updateOrCreate(
            ['user_id' => $user->id, 'product_id' => $product->id],
            ['quantity' => $nextQuantity]
        );
    }

    /**
     * Perbarui kuantitas dan catatan sebuah item keranjang dengan cek stok.
     */
    public function update(User $user, Product $product, int $quantity, ?string $note = null): void
    {
        if ($quantity > $product->stock) {
            throw ValidationException::withMessages(['cart' => "Stok {$product->name} tidak mencukupi."]);
        }

        CartItem::query()->updateOrCreate(
            ['user_id' => $user->id, 'product_id' => $product->id],
            ['quantity' => $quantity, 'note' => $note]
        );
    }

    public function remove(User $user, Product $product): void
    {
        CartItem::query()->where('user_id', $user->id)->where('product_id', $product->id)->delete();
    }

    public function clear(User $user): void
    {
        CartItem::query()->where('user_id', $user->id)->delete();
    }
}
