<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Shift;
use App\Models\StockProduct;
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
        //$electronics = ProductCategory::create(['category_name' => 'Electronics']);
        //$clothing = ProductCategory::create(['category_name' => 'Clothing']);
        //$books = ProductCategory::create(['category_name' => 'Books']);

        // Create sample products
        /* Product::create([
            'name' => 'iPhone 15 Pro',
            'image' => '',
            'description' => 'Latest iPhone with advanced features',
            'price' => 50000.00,
            'status' => 0,
            'created_by' => $admin->id,
            'created_at' => now()
        ]);

        Product::create([
            'name' => 'Laravel Up & Running',
            'image' => '',
            'description' => 'Learn Laravel framework from scratch',
            'price' => 20000.00,
            'status' => 0,
            'created_by' => $admin->id,
            'created_at' => now()
        ]); */

        // Assuming we already have users and products from previous seeders
        //$admin = User::where('email', 'admin@example.com')->first();
        //$customer = User::where('email', 'cashier@example.com')->first();

        // Create sample stocks for products
        /* StockProduct::create([
            'product_id' => Product::where('name', 'iPhone 15 Pro')->first()->id,
            'user_id' => $customer->id,
            'quantity' => 10,
            'created_by' => $admin->id,
            'created_at' => now()
        ]);
        StockProduct::create([
            'product_id' => Product::where('name', 'Laravel Up & Running')->first()->id,
            'user_id' => $customer->id,
            'quantity' => 5,
            'created_by' => $admin->id,
            'created_at' => now()
        ]); */

        // Create sample shift for cashier
        /* $shift = Shift::create([
            'user_id' => $customer->id,
            'cash_balance' => 1000.00,
            'expected_cash_balance' => 1000.00,
            'final_cash_balance' => 0.00,
            'created_at' => now(),
            'created_by' => $admin->id
        ]); */

        // Create sample transactions for cashier
        /* $commonData = [
            'shift_id' => $shift->id,
            'payment_method' => 'cash',
            'total_tax' => 0.00,
            'type_discount' => 0,
            'amount_discount' => 0,
        ]; */
        
        // Create transaction 1 (paid)
        /* $transaction1 = Transaction::create(array_merge([
            'user_id' => $customer->id,
            'date' => now(),
            'total_price' => 70000.00,
            'total_payment' => 100000.00,
            'payment_status' => 'paid',
            'created_by' => $admin->id,
            'created_at' => now(),
        ], $commonData));
        
        TransactionDetail::create([
            'trans_id' => $transaction1->id,
            'product_id' => Product::where('name', 'iPhone 15 Pro')->first()->id,
            'quantity' => 1,
            'price' => 50000.00,
            'subtotal' => 50000.00,
        ]);
        
        TransactionDetail::create([
            'trans_id' => $transaction1->id,
            'product_id' => Product::where('name', 'Laravel Up & Running')->first()->id,
            'quantity' => 1,
            'price' => 20000.00,
            'subtotal' => 20000.00,
        ]); */
        
        // Create transaction 2 (paid)
        /* $transaction2 = Transaction::create(array_merge([
            'user_id' => $customer->id,
            'date' => now()->subDays(2),
            'total_price' => 40000.00,
            'total_payment' => 40000.00,
            'payment_status' => 'paid',
            'created_by' => $admin->id,
            'created_at' => now()->subDays(2),
        ], $commonData));
        
        TransactionDetail::create([
            'trans_id' => $transaction2->id,
            'product_id' => Product::where('name', 'Laravel Up & Running')->first()->id,
            'quantity' => 2,
            'price' => 20000.00,
            'subtotal' => 40000.00,
        ]); */
        
        /* $shift->update([
            'expected_cash_balance' => $shift->expected_cash_balance + $transaction1->total_price + $transaction2->total_price,
        ]); */
        
        // Create transaction 3 (failed)
        /* $transaction3 = Transaction::create(array_merge([
            'user_id' => $customer->id,
            'date' => now()->subDays(5),
            'total_price' => 50000.00,
            'total_payment' => 50000.00,
            'payment_status' => 'failed',
            'created_by' => $admin->id,
            'created_at' => now()->subDays(5),
            'total_payment' => 0,
        ], $commonData));
        
        TransactionDetail::create([
            'trans_id' => $transaction3->id,
            'product_id' => Product::where('name', 'iPhone 15 Pro')->first()->id,
            'quantity' => 1,
            'price' => 50000.00,
            'subtotal' => 50000.00,
        ]); */
    }
}
