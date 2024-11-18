<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Transaction;
use App\Models\TransactionDetail;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Create admin user
        $admin = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
        ]);
        $customer = User::factory()->create([
            'name' => 'Customer User',
            'email' => 'customer@example.com',
            'password' => Hash::make('password'),
        ]);

        // Create product categories
        $electronics = ProductCategory::create(['category_name' => 'Electronics']);
        $clothing = ProductCategory::create(['category_name' => 'Clothing']);
        $books = ProductCategory::create(['category_name' => 'Books']);

        // Create sample products
        Product::create([
            'product_category_id' => $electronics->product_category_id,
            'product_name' => 'iPhone 15 Pro',
            'picture' => 'https://www.digimap.co.id/cdn/shop/files/iPhone_15_Pro_Max_Blue_Titanium_PDP_Image_Position-1__GBEN.jpg',
            'stock' => 50,
            'price' => 999.99,
            'desc_product' => 'Latest iPhone with advanced features',
            'discount_type' => 'percentage',
            'discount_amount' => 10,
            'start_date_disc' => now(),
            'end_date_disc' => now()->addDays(30),
            'created_by' => $admin->id,
            'created_date' => now()
        ]);

        Product::create([
            'product_category_id' => $electronics->product_category_id,
            'product_name' => 'Samsung Galaxy S24',
            'picture' => 'https://www.static-src.com/wcsstore/Indraprastha/images/catalog/full/catalog-image/97/MTA-154133629/brd-44261_samsung-galaxy-s24-5g-8-256gb_full02-d8b8db91.jpg',
            'stock' => 45,
            'price' => 899.99,
            'desc_product' => 'Latest Samsung flagship phone',
            'discount_type' => 'fixed',
            'discount_amount' => 100,
            'start_date_disc' => now(),
            'end_date_disc' => now()->addDays(15),
            'created_by' => $admin->id,
            'created_date' => now()
        ]);

        Product::create([
            'product_category_id' => $clothing->product_category_id,
            'product_name' => 'Nike Air Max',
            'picture' => 'https://www.footlocker.id/media/catalog/product/cache/f57d6f7ebc711fc328170f0ddc174b08/0/1/01-NIKE-FFSSBNIK5-NIKDZ2628102-White.jpg',
            'stock' => 100,
            'price' => 129.99,
            'desc_product' => 'Comfortable running shoes',
            'created_by' => $admin->id,
            'created_date' => now()
        ]);

        Product::create([
            'product_category_id' => $books->product_category_id,
            'product_name' => 'Laravel Up & Running',
            'picture' => 'https://www.static-src.com/wcsstore/Indraprastha/images/catalog/full/catalog-image/95/MTA-176356210/no-brand_no-brand_full01.jpg',
            'stock' => 75,
            'price' => 49.99,
            'desc_product' => 'Learn Laravel framework from scratch',
            'discount_type' => 'percentage',
            'discount_amount' => 15,
            'start_date_disc' => now(),
            'end_date_disc' => now()->addDays(7),
            'created_by' => $admin->id,
            'created_date' => now()
        ]);

        // Assuming we already have users and products from previous seeders
        $admin = User::where('email', 'admin@example.com')->first();
        $customer = User::where('email', 'customer@example.com')->first();
        
        // Create sample transactions
        $transaction1 = Transaction::create([
            'user_id' => $customer->id,
            'date' => now(),
            'total_price' => 1049.98, // Will be calculated from details
            'payment_status' => 'paid',
            'created_by' => $admin->id,
            'created_date' => now()
        ]);

        // Create transaction details for first transaction
        TransactionDetail::create([
            'trans_id' => $transaction1->trans_id,
            'product_id' => Product::where('product_name', 'iPhone 15 Pro')->first()->product_id,
            'qty' => 1,
            'price' => 999.99,
            'subtotal' => 999.99,
            'created_by' => $admin->id,
            'created_date' => now()
        ]);

        TransactionDetail::create([
            'trans_id' => $transaction1->trans_id,
            'product_id' => Product::where('product_name', 'Laravel Up & Running')->first()->product_id,
            'qty' => 1,
            'price' => 49.99,
            'subtotal' => 49.99,
            'created_by' => $admin->id,
            'created_date' => now()
        ]);

        // Create another transaction
        $transaction2 = Transaction::create([
            'user_id' => $customer->id,
            'date' => now()->subDays(2),
            'total_price' => 1999.98,
            'payment_status' => 'pending',
            'created_by' => $admin->id,
            'created_date' => now()->subDays(2)
        ]);

        // Create transaction details for second transaction
        TransactionDetail::create([
            'trans_id' => $transaction2->trans_id,
            'product_id' => Product::where('product_name', 'iPhone 15 Pro')->first()->product_id,
            'qty' => 2,
            'price' => 999.99,
            'subtotal' => 1999.98,
            'created_by' => $admin->id,
            'created_date' => now()->subDays(2)
        ]);

        // Create a failed transaction
        $transaction3 = Transaction::create([
            'user_id' => $customer->id,
            'date' => now()->subDays(5),
            'total_price' => 49.99,
            'payment_status' => 'failed',
            'created_by' => $admin->id,
            'created_date' => now()->subDays(5)
        ]);

        TransactionDetail::create([
            'trans_id' => $transaction3->trans_id,
            'product_id' => Product::where('product_name', 'Laravel Up & Running')->first()->product_id,
            'qty' => 1,
            'price' => 49.99,
            'subtotal' => 49.99,
            'created_by' => $admin->id,
            'created_date' => now()->subDays(5)
        ]);
    }
}
