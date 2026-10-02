<?php

namespace Tests;

use App\Models\CartItem;
use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Isi keranjang database milik pembeli (product_id => quantity, plus catatan opsional).
     *
     * @param  array<int, int>  $cart
     * @param  array<int, string|null>  $notes
     */
    protected function cart(User $buyer, array $cart, array $notes = []): void
    {
        foreach ($cart as $productId => $quantity) {
            CartItem::query()->updateOrCreate(
                ['user_id' => $buyer->id, 'product_id' => $productId],
                ['quantity' => $quantity, 'note' => $notes[$productId] ?? null]
            );
        }
    }
}
