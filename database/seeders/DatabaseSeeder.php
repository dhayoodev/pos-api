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
            'phone' => '081234567890',
            'password' => Hash::make('password'),
            'role' => 0,
            'status' => 0,
        ]);
        $customer = User::factory()->create([
            'name' => 'Cashier User',
            'email' => 'cashier@example.com',
            'phone' => '081234567891',
            'password' => Hash::make('password'),
            'role' => 1,
            'status' => 0,
        ]);

        // Create product categories
        /* $electronics = ProductCategory::create(['category_name' => 'Electronics']);
        $clothing = ProductCategory::create(['category_name' => 'Clothing']);
        $books = ProductCategory::create(['category_name' => 'Books']); */

        // Create sample products
       /*  Product::create([
            'name' => 'iPhone 15 Pro',
            'image' => 'https://www.digimap.co.id/cdn/shop/files/iPhone_15_Pro_Max_Blue_Titanium_PDP_Image_Position-1__GBEN.jpg',
            'description' => 'Latest iPhone with advanced features',
            'price' => 999.99,
            'status' => 0,
            'created_by' => $admin->id,
            'created_at' => now()
        ]);

        Product::create([
            'name' => 'Samsung Galaxy S24',
            'image' => 'https://www.static-src.com/wcsstore/Indraprastha/images/catalog/full/catalog-image/97/MTA-154133629/brd-44261_samsung-galaxy-s24-5g-8-256gb_full02-d8b8db91.jpg',
            'description' => 'Latest Samsung flagship phone',
            'price' => 899.99,
            'status' => 0,
            'created_by' => $admin->id,
            'created_at' => now()
        ]);

        Product::create([
            'name' => 'Nike Air Max',
            'image' => 'https://www.footlocker.id/media/catalog/product/cache/f57d6f7ebc711fc328170f0ddc174b08/0/1/01-NIKE-FFSSBNIK5-NIKDZ2628102-White.jpg',
            'description' => 'Comfortable running shoes',
            'price' => 129.99,
            'status' => 0,
            'created_by' => $admin->id,
            'created_at' => now()
        ]);

        Product::create([
            'name' => 'Laravel Up & Running',
            'image' => 'https://www.static-src.com/wcsstore/Indraprastha/images/catalog/full/catalog-image/95/MTA-176356210/no-brand_no-brand_full01.jpg',
            'description' => 'Learn Laravel framework from scratch',
            'price' => 49.99,
            'status' => 0,
            'created_by' => $admin->id,
            'created_at' => now()
        ]); */

        // Assuming we already have users and products from previous seeders
        $admin = User::where('email', 'admin@example.com')->first();
        $customer = User::where('email', 'cashier@example.com')->first();
        
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
            'product_id' => Product::where('name', 'iPhone 15 Pro')->first()->id,
            'qty' => 1,
            'price' => 999.99,
            'subtotal' => 999.99,
            'created_by' => $admin->id,
            'created_date' => now()
        ]);

        TransactionDetail::create([
            'trans_id' => $transaction1->trans_id,
            'product_id' => Product::where('name', 'Laravel Up & Running')->first()->id,
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
            'product_id' => Product::where('name', 'iPhone 15 Pro')->first()->id,
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
            'product_id' => Product::where('name', 'Laravel Up & Running')->first()->id,
            'qty' => 1,
            'price' => 49.99,
            'subtotal' => 49.99,
            'created_by' => $admin->id,
            'created_date' => now()->subDays(5)
        ]);
    }
}
