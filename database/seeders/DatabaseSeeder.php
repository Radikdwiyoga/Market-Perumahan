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
        // 1. Akun Admin
        User::updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Demo Admin',
                'phone' => '081200000099',
                'address' => 'Perumahan Villa Nusa',
                'block' => 'A1',
                'house_number' => '99',
                'role' => 'admin',
                'status' => 'active',
                'password' => 'password',
                'email_verified_at' => now(),
            ],
        );

        // 2. Akun Buyer / Warga
        User::updateOrCreate(
            ['email' => 'buyer@example.com'],
            [
                'name' => 'Siti Aminah (Buyer)',
                'phone' => '081200000001',
                'address' => 'Perumahan Villa Nusa',
                'block' => 'B2',
                'house_number' => '15',
                'role' => 'buyer',
                'status' => 'active',
                'password' => 'password',
                'email_verified_at' => now(),
            ],
        );

        // 3. Akun Seller 1 - Warung Sembako & Minuman
        $seller1 = User::updateOrCreate(
            ['email' => 'seller1@example.com'],
            [
                'name' => 'Pak Budi (Seller Sembako)',
                'phone' => '081298765401',
                'address' => 'Perumahan Villa Nusa',
                'block' => 'A1',
                'house_number' => '01',
                'role' => 'seller',
                'status' => 'active',
                'password' => 'password',
                'email_verified_at' => now(),
            ],
        );

        $store1 = SellerProfile::updateOrCreate(
            ['user_id' => $seller1->id],
            [
                'store_name' => 'Warung Berkah Tetangga',
                'description' => 'Sembako segar, beras pulen, minyak goreng dan kebutuhan pokok harian warga Villa Nusa.',
                'phone' => $seller1->phone,
                'address' => 'Blok A1 No. 01, Perumahan Villa Nusa',
                'image' => 'stores/warung-berkah.jpg',
                'status' => 'open',
                'verification_status' => 'approved',
                'verified_at' => now(),
                'enable_delivery' => true,
                'enable_pickup' => true,
                'delivery_fee' => 3000,
                'min_order_amount' => 10000,
            ],
        );

        // 4. Akun Seller 2 - Kuliner & Makanan Siap Saji
        $seller2 = User::updateOrCreate(
            ['email' => 'seller2@example.com'],
            [
                'name' => 'Bu Dewi (Seller Kuliner)',
                'phone' => '081298765402',
                'address' => 'Perumahan Villa Nusa',
                'block' => 'C3',
                'house_number' => '08',
                'role' => 'seller',
                'status' => 'active',
                'password' => 'password',
                'email_verified_at' => now(),
            ],
        );

        $store2 = SellerProfile::updateOrCreate(
            ['user_id' => $seller2->id],
            [
                'store_name' => 'Dapur Rasa Bunda Dewi',
                'description' => 'Masakan rumahan lezat, higienis, halal dan selalu hangat untuk santap bersama keluarga.',
                'phone' => $seller2->phone,
                'address' => 'Blok C3 No. 08, Perumahan Villa Nusa',
                'image' => 'stores/dapur-dewi.jpg',
                'status' => 'open',
                'verification_status' => 'approved',
                'verified_at' => now(),
                'enable_delivery' => true,
                'enable_pickup' => true,
                'delivery_fee' => 2000,
                'min_order_amount' => 15000,
            ],
        );

        // Kategori Produk
        $categories = collect([
            'Sembako' => 'Kebutuhan pokok beras, minyak, gula, dan telur',
            'Makanan & Kuliner' => 'Makanan siap santap hangat dari dapur warga',
            'Minuman Segar' => 'Aneka minuman pelepas dahaga',
            'Camilan & Snack' => 'Kue kering, gorengan dan camilan santai',
            'Rumah Tangga' => 'Kebutuhan perlengkapan harian rumah tangga',
        ])->mapWithKeys(function (string $desc, string $name) {
            return [$name => Category::updateOrCreate(['name' => $name], ['description' => $desc, 'status' => 'active'])];
        });

        // Produk Toko 1 (Warung Berkah Tetangga)
        Product::updateOrCreate(
            ['seller_profile_id' => $store1->id, 'name' => 'Beras Pandan Wangi Premium 5 Kg'],
            [
                'category_id' => $categories['Sembako']->id,
                'description' => 'Beras kualitas super wangi dan pulen asli panen petani lokal. Bersih tanpa pemutih.',
                'price' => 74000,
                'stock' => 25,
                'image' => 'products/beras-pandan-wangi.jpg',
                'status' => 'active',
            ],
        );

        Product::updateOrCreate(
            ['seller_profile_id' => $store1->id, 'name' => 'Minyak Goreng Sawit 2 Liter'],
            [
                'category_id' => $categories['Sembako']->id,
                'description' => 'Minyak goreng jernih 2x penyaringan, cocok untuk gorengan renyah keluarga.',
                'price' => 34000,
                'stock' => 40,
                'image' => 'products/minyak-goreng.jpg',
                'status' => 'active',
            ],
        );

        Product::updateOrCreate(
            ['seller_profile_id' => $store1->id, 'name' => 'Telur Ayam Negeri 1 Kg (Isi 16 Butir)'],
            [
                'category_id' => $categories['Sembako']->id,
                'description' => 'Telur ayam segar langsung dari peternakan, selalu baru setiap pagi.',
                'price' => 28000,
                'stock' => 30,
                'image' => 'products/telur-ayam.jpg',
                'status' => 'active',
            ],
        );

        Product::updateOrCreate(
            ['seller_profile_id' => $store1->id, 'name' => 'Gula Pasir Kristal Putih 1 Kg'],
            [
                'category_id' => $categories['Sembako']->id,
                'description' => 'Gula pasir kristal murni untuk teh manis dan kebutuhan masak dapur.',
                'price' => 17500,
                'stock' => 50,
                'image' => 'products/gula-pasir.jpg',
                'status' => 'active',
            ],
        );

        Product::updateOrCreate(
            ['seller_profile_id' => $store1->id, 'name' => 'Sabun Cuci Piring Jeruk Nipis 750ml'],
            [
                'category_id' => $categories['Rumah Tangga']->id,
                'description' => 'Formula ekstrak jeruk nipis ampuh hilangkan lemak dan bau amis seketika.',
                'price' => 13500,
                'stock' => 20,
                'image' => 'products/sabun-cuci-piring.jpg',
                'status' => 'active',
            ],
        );

        // Produk Toko 2 (Dapur Rasa Bunda Dewi)
        Product::updateOrCreate(
            ['seller_profile_id' => $store2->id, 'name' => 'Nasi Bakar Cakalang Suwir Pedas'],
            [
                'category_id' => $categories['Makanan & Kuliner']->id,
                'description' => 'Nasi bakar gurih aroma daun pisang dengan isian ikan cakalang suwir bumbu rica pedas mantap.',
                'price' => 20000,
                'stock' => 15,
                'image' => 'products/nasi-bakar.jpg',
                'status' => 'active',
            ],
        );

        Product::updateOrCreate(
            ['seller_profile_id' => $store2->id, 'name' => 'Ayam Goreng Lengkuas + Sambal Terasi'],
            [
                'category_id' => $categories['Makanan & Kuliner']->id,
                'description' => '1 potong paha/dada ayam bumbu lengkuas kriuk renyah lengkap lalapan dan sambal.',
                'price' => 22000,
                'stock' => 18,
                'image' => 'products/ayam-goreng-lengkuas.jpg',
                'status' => 'active',
            ],
        );

        Product::updateOrCreate(
            ['seller_profile_id' => $store2->id, 'name' => 'Pastel Isi Sayur & Telur (Isi 5 Pcs)'],
            [
                'category_id' => $categories['Camilan & Snack']->id,
                'description' => 'Kulit renyah berlapis dengan isian wortel, bihun, dan telur rebus. Disajikan dengan cabai rawit hijau.',
                'price' => 15000,
                'stock' => 20,
                'image' => 'products/pastel-sayur.jpg',
                'status' => 'active',
            ],
        );

        Product::updateOrCreate(
            ['seller_profile_id' => $store2->id, 'name' => 'Es Cendol Nangka Gula Aren'],
            [
                'category_id' => $categories['Minuman Segar']->id,
                'description' => 'Cendol kenyal pandan asli dengan santan gurih, potongan nangka wangi dan gula aren murni.',
                'price' => 12000,
                'stock' => 25,
                'image' => 'products/es-cendol.jpg',
                'status' => 'active',
            ],
        );

        Product::updateOrCreate(
            ['seller_profile_id' => $store2->id, 'name' => 'Es Lemon Tea Segar Selasih'],
            [
                'category_id' => $categories['Minuman Segar']->id,
                'description' => 'Teh segar dengan perasan lemon asli dan taburan biji selasih dingin menyegarkan.',
                'price' => 8000,
                'stock' => 30,
                'image' => 'products/es-lemon-tea.jpg',
                'status' => 'active',
            ],
        );
    }
}
