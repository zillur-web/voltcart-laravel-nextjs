<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CatalogSeeder extends Seeder
{
    public function run(): void
    {
        $tree = [
            'Casual' => ['T-Shirts', 'Jeans', 'Hoodies'],
            'Formal' => ['Shirts', 'Trousers'],
            'Party' => ['Dresses'],
            'Gym' => ['Activewear', 'Sneakers'],
        ];

        $images = [
            'Casual' => 'https://images.unsplash.com/photo-1489987707025-afc232f7ea0f?auto=format&fit=crop&w=900&q=80',
            'Formal' => 'https://images.unsplash.com/photo-1594938291221-94f18cbb5660?auto=format&fit=crop&w=900&q=80',
            'Party' => 'https://images.unsplash.com/photo-1496747611176-843222e1e57c?auto=format&fit=crop&w=900&q=80',
            'Gym' => 'https://images.unsplash.com/photo-1518310383802-640c2de311b2?auto=format&fit=crop&w=900&q=80',
        ];

        $categories = [];
        $order = 1;
        foreach ($tree as $parentName => $children) {
            $parent = Category::query()->updateOrCreate(
                ['slug' => Str::slug($parentName)],
                [
                    'name' => $parentName,
                    'parent_id' => null,
                    'image' => $images[$parentName],
                    'is_active' => true,
                    'sort_order' => $order++,
                ]
            );
            foreach ($children as $index => $childName) {
                $child = Category::query()->updateOrCreate(
                    ['slug' => Str::slug($childName)],
                    [
                        'name' => $childName,
                        'parent_id' => $parent->id,
                        'image' => $images[$parentName],
                        'is_active' => true,
                        'sort_order' => $index + 1,
                    ]
                );
                $categories[$childName] = $child->id;
            }
        }

        $photos = [
            'https://images.unsplash.com/photo-1521572163474-6864f9cf17ab?auto=format&fit=crop&w=900&q=80',
            'https://images.unsplash.com/photo-1583743814966-8936f5b7be1a?auto=format&fit=crop&w=900&q=80',
            'https://images.unsplash.com/photo-1576566588028-4147f3842f27?auto=format&fit=crop&w=900&q=80',
            'https://images.unsplash.com/photo-1618354691373-d851c5c3a990?auto=format&fit=crop&w=900&q=80',
            'https://images.unsplash.com/photo-1542272604-787c3835535d?auto=format&fit=crop&w=900&q=80',
            'https://images.unsplash.com/photo-1541099649105-f69ad21f3246?auto=format&fit=crop&w=900&q=80',
            'https://images.unsplash.com/photo-1473966968600-fa801b869a1a?auto=format&fit=crop&w=900&q=80',
            'https://images.unsplash.com/photo-1556821840-3a63f95609a7?auto=format&fit=crop&w=900&q=80',
            'https://images.unsplash.com/photo-1556821840-3a63f95609a7?auto=format&fit=crop&w=900&q=80',
            'https://images.unsplash.com/photo-1434389677669-e08b4cac3105?auto=format&fit=crop&w=900&q=80',
            'https://images.unsplash.com/photo-1594938291221-94f18cbb5660?auto=format&fit=crop&w=900&q=80',
            'https://images.unsplash.com/photo-1602810318383-e386cc2a3ccf?auto=format&fit=crop&w=900&q=80',
            'https://images.unsplash.com/photo-1598033129183-c4f50c736f10?auto=format&fit=crop&w=900&q=80',
            'https://images.unsplash.com/photo-1624378439575-d8705ad7ae80?auto=format&fit=crop&w=900&q=80',
            'https://images.unsplash.com/photo-1473966968600-fa801b869a1a?auto=format&fit=crop&w=900&q=80',
            'https://images.unsplash.com/photo-1594938298603-c8148c4dae35?auto=format&fit=crop&w=900&q=80',
            'https://images.unsplash.com/photo-1496747611176-843222e1e57c?auto=format&fit=crop&w=900&q=80',
            'https://images.unsplash.com/photo-1539008835657-9e8e9680c956?auto=format&fit=crop&w=900&q=80',
            'https://images.unsplash.com/photo-1515886657613-9f3515b0c78f?auto=format&fit=crop&w=900&q=80',
            'https://images.unsplash.com/photo-1518310383802-640c2de311b2?auto=format&fit=crop&w=900&q=80',
            'https://images.unsplash.com/photo-1571902943202-507ec2618e8f?auto=format&fit=crop&w=900&q=80',
            'https://images.unsplash.com/photo-1518310383802-640c2de311b2?auto=format&fit=crop&w=900&q=80',
            'https://images.unsplash.com/photo-1542291026-7eec264c27ff?auto=format&fit=crop&w=900&q=80',
            'https://images.unsplash.com/photo-1549298916-b41d501d3772?auto=format&fit=crop&w=900&q=80',
            'https://images.unsplash.com/photo-1600185365483-26d7a4cc7519?auto=format&fit=crop&w=900&q=80',
        ];

        $products = [
            ['Tape Detail Tee', 'T-Shirts', 120, 96, 'Black', ['S', 'M', 'L', 'XL'], 25, 4.5, 210],
            ['Vertical Striped Shirt', 'T-Shirts', 232, 212, 'Green', ['S', 'M', 'L'], 18, 5.0, 140],
            ['Courage Graphic Tee', 'T-Shirts', 145, null, 'Orange', ['M', 'L', 'XL'], 30, 4.0, 98],
            ['Loose Fit Tee', 'T-Shirts', 80, null, 'White', ['S', 'M', 'L', 'XL'], 40, 4.2, 64],
            ['Skinny Jeans', 'Jeans', 240, 180, 'Blue', ['30', '32', '34', '36'], 16, 4.6, 188],
            ['Faded Black Jeans', 'Jeans', 210, null, 'Black', ['30', '32', '34'], 12, 4.3, 76],
            ['Relaxed Denim', 'Jeans', 180, 150, 'Blue', ['32', '34', '36'], 20, 4.1, 54],
            ['Checkered Shirt', 'Shirts', 180, null, 'Red', ['S', 'M', 'L'], 14, 4.4, 120],
            ['Oxford Shirt', 'Shirts', 160, 130, 'White', ['S', 'M', 'L', 'XL'], 22, 4.7, 201],
            ['Archive Linen Shirt', 'Shirts', 190, null, 'Beige', ['M', 'L'], 0, 4.0, 12],
            ['Sleeve Stripe Shirt', 'Shirts', 160, 130, 'Blue', ['S', 'M', 'L'], 3, 4.5, 88],
            ['Pleated Trousers', 'Trousers', 220, null, 'Black', ['30', '32', '34'], 15, 4.2, 41],
            ['Wool Dress Trousers', 'Trousers', 260, 210, 'Gray', ['32', '34', '36'], 11, 4.8, 63],
            ['One Life Hoodie', 'Hoodies', 260, 190, 'Green', ['M', 'L', 'XL'], 19, 4.6, 154],
            ['Black Essential Hoodie', 'Hoodies', 220, null, 'Black', ['S', 'M', 'L', 'XL'], 28, 4.4, 97],
            ['Gradient Party Dress', 'Dresses', 320, 240, 'Pink', ['XS', 'S', 'M', 'L'], 9, 4.9, 73],
            ['Satin Slip Dress', 'Dresses', 280, null, 'Black', ['XS', 'S', 'M'], 7, 4.7, 39],
            ['Printed Midi Dress', 'Dresses', 300, 255, 'Yellow', ['S', 'M', 'L'], 10, 4.3, 28],
            ['Training Shorts', 'Activewear', 90, 70, 'Black', ['S', 'M', 'L'], 34, 4.1, 112],
            ['Studio Leggings', 'Activewear', 110, null, 'Purple', ['XS', 'S', 'M', 'L'], 26, 4.8, 166],
            ['Court Sneakers', 'Sneakers', 180, 140, 'Red', ['7', '8', '9', '10', '11'], 21, 4.6, 230],
            ['Court Classic', 'Sneakers', 160, null, 'White', ['7', '8', '9', '10'], 17, 4.4, 91],
            ['Runner Mesh', 'Sneakers', 200, 165, 'Gray', ['8', '9', '10', '11'], 13, 4.2, 47],
            ['Gym Crew Tee', 'Activewear', 75, null, 'White', ['S', 'M', 'L', 'XL'], 36, 4.0, 58],
        ];

        foreach ($products as $index => [$name, $category, $price, $discount, $color, $sizes, $stock, $rating, $reviews]) {
            $product = Product::query()->updateOrCreate(
                ['sku' => 'SC-'.str_pad((string) ($index + 1), 4, '0', STR_PAD_LEFT)],
                [
                    'category_id' => $categories[$category],
                    'name' => $name,
                    'slug' => Str::slug($name),
                    'description' => $name.' from the SHOP.CO edit. Cut in a mid weight fabric with a clean finish, made to layer with the rest of the collection. Machine wash cold.',
                    'price' => $price,
                    'discount_price' => $discount,
                    'stock' => $stock,
                    'color' => $color,
                    'sizes' => $sizes,
                    'rating' => $rating,
                    'reviews_count' => $reviews,
                    'is_active' => true,
                ]
            );

            $product->images()->delete();
            $product->images()->create([
                'path' => $photos[$index],
                'sort_order' => 0,
                'is_primary' => true,
            ]);
            $product->images()->create([
                'path' => $photos[($index + 3) % count($photos)],
                'sort_order' => 1,
                'is_primary' => false,
            ]);
        }
    }
}
