<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\SellerProfile;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::updateOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test User',
                'phone' => '081234567890',
                'address' => 'Perumahan ABC',
                'block' => 'A1',
                'house_number' => '8',
                'role' => 'buyer',
                'status' => 'active',
                'password' => 'password',
                'email_verified_at' => now(),
            ],
        );

        User::updateOrCreate(
            ['email' => 'demo.buyer@example.com'],
            [
                'name' => 'Demo Buyer',
                'phone' => '081200000001',
                'address' => 'Perumahan ABC',
                'block' => 'A1',
                'house_number' => '10',
                'role' => 'buyer',
                'status' => 'active',
                'password' => 'password',
                'email_verified_at' => now(),
            ],
        );

        User::updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Demo Admin',
                'phone' => '081200000099',
                'address' => 'Perumahan ABC',
                'block' => 'A1',
                'house_number' => '99',
                'role' => 'admin',
                'status' => 'active',
                'password' => 'password',
                'email_verified_at' => now(),
            ],
        );

        $seller = User::updateOrCreate(
            ['email' => 'budi@example.com'],
            [
                'name' => 'Budi Santoso',
                'phone' => '081298765432',
                'address' => 'Perumahan ABC',
                'block' => 'A1',
                'house_number' => '1',
                'role' => 'seller',
                'status' => 'active',
                'password' => 'password',
                'email_verified_at' => now(),
            ],
        );

        $store = SellerProfile::updateOrCreate(
            ['user_id' => $seller->id],
            [
                'store_name' => 'Warung Tetangga',
                'description' => 'Kebutuhan harian warga, dekat dan praktis.',
                'phone' => $seller->phone,
                'address' => 'Blok A1, Perumahan ABC',
                'status' => 'open',
            ],
        );

        $categories = collect(['Sembako', 'Makanan', 'Minuman', 'Rumah Tangga'])
            ->mapWithKeys(fn (string $name) => [$name => Category::updateOrCreate(['name' => $name], ['status' => 'active'])]);

        Product::updateOrCreate(
            ['seller_profile_id' => $store->id, 'name' => 'Beras Pulen 5 Kg'],
            [
                'category_id' => $categories['Sembako']->id,
                'description' => 'Beras pulen pilihan untuk kebutuhan keluarga.',
                'price' => 76000,
                'stock' => 24,
                'status' => 'active',
            ],
        );

        Product::updateOrCreate(
            ['seller_profile_id' => $store->id, 'name' => 'Nasi Bakar Ayam'],
            [
                'category_id' => $categories['Makanan']->id,
                'description' => 'Nasi bakar hangat dengan ayam suwir berbumbu.',
                'price' => 18000,
                'stock' => 12,
                'status' => 'active',
            ],
        );

        Product::updateOrCreate(
            ['seller_profile_id' => $store->id, 'name' => 'Es Teh Manis'],
            [
                'category_id' => $categories['Minuman']->id,
                'description' => 'Minuman segar untuk menemani aktivitas di rumah.',
                'price' => 6000,
                'stock' => 30,
                'status' => 'active',
            ],
        );
    }
}
