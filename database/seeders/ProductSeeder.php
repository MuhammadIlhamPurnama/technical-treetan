<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $products = [
            [
                'name' => 'iPhone 15 Pro',
                'description' => 'Latest iPhone with A17 Pro chip and titanium design',
                'price' => 15999000,
                'stock' => 25,
                'category' => 'Electronics',
                'image' => 'https://example.com/iphone15pro.jpg',
                'status' => 'active',
            ],
            [
                'name' => 'Samsung Galaxy S24 Ultra',
                'description' => 'Premium Android smartphone with S Pen and advanced camera',
                'price' => 18999000,
                'stock' => 30,
                'category' => 'Electronics',
                'image' => 'https://example.com/galaxys24ultra.jpg',
                'status' => 'active',
            ],
            [
                'name' => 'MacBook Air M3',
                'description' => '13-inch MacBook Air with M3 chip, 8GB RAM, 256GB SSD',
                'price' => 17999000,
                'stock' => 15,
                'category' => 'Computers',
                'image' => 'https://example.com/macbookair.jpg',
                'status' => 'active',
            ],
            [
                'name' => 'Nike Air Jordan 1',
                'description' => 'Classic basketball sneakers in various colors',
                'price' => 2499000,
                'stock' => 50,
                'category' => 'Fashion',
                'image' => 'https://example.com/airjordan1.jpg',
                'status' => 'active',
            ],
            [
                'name' => 'Sony WH-1000XM5',
                'description' => 'Wireless noise-canceling headphones with premium sound',
                'price' => 4999000,
                'stock' => 40,
                'category' => 'Electronics',
                'image' => 'https://example.com/sonywh1000xm5.jpg',
                'status' => 'active',
            ],
            [
                'name' => 'Uniqlo Heattech T-Shirt',
                'description' => 'Warm and comfortable innerwear for cold weather',
                'price' => 199000,
                'stock' => 100,
                'category' => 'Fashion',
                'image' => 'https://example.com/heattech.jpg',
                'status' => 'active',
            ],
            [
                'name' => 'Gaming Chair Pro',
                'description' => 'Ergonomic gaming chair with lumbar support and adjustable height',
                'price' => 3499000,
                'stock' => 20,
                'category' => 'Furniture',
                'image' => 'https://example.com/gamingchair.jpg',
                'status' => 'active',
            ],
            [
                'name' => 'Mechanical Keyboard RGB',
                'description' => 'Premium mechanical keyboard with RGB lighting and blue switches',
                'price' => 1299000,
                'stock' => 35,
                'category' => 'Electronics',
                'image' => 'https://example.com/keyboard.jpg',
                'status' => 'active',
            ],
            [
                'name' => 'Vintage Watch',
                'description' => 'Classic analog watch with leather strap - Limited Edition',
                'price' => 899000,
                'stock' => 0,
                'category' => 'Fashion',
                'image' => 'https://example.com/vintagewatch.jpg',
                'status' => 'inactive',
            ],
            [
                'name' => 'Coffee Maker Deluxe',
                'description' => 'Automatic coffee maker with programmable timer and thermal carafe',
                'price' => 2199000,
                'stock' => 12,
                'category' => 'Home & Kitchen',
                'image' => 'https://example.com/coffeemaker.jpg',
                'status' => 'active',
            ],
        ];

        foreach ($products as $product) {
            \App\Models\Product::create($product);
        }
    }
}
