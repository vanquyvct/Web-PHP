<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new RuntimeException('Demo seeding is restricted to local/testing environments.');
        }
        $password = config('demo.admin_password');
        $admin = User::where('email', 'admin@greenfood.test')->first();
        if ($admin && (int) $admin->Role !== 1) {
            throw new RuntimeException('Demo admin email belongs to a customer. No account will be promoted.');
        }
        if (! $admin && (! is_string($password) || strlen($password) < 12)) {
            throw new RuntimeException('Set DEMO_ADMIN_PASSWORD to at least 12 characters in your local .env before seeding.');
        }

        // Categories match the current storefront filters; prices are VND per named pack.
        $products = [
            ['Cà rốt Đà Lạt - 500 g', 'Rau củ', 18000, 60, 0.10, 'carot.jpg'],
            ['Khoai tây - 1 kg', 'Rau củ', 35000, 45, 0, 'khoaitay.png'],
            ['Ớt chuông - 500 g', 'Rau củ', 42000, 30, 0.15, 'otchuong.png'],
            ['Hành Lý Sơn - 250 g', 'Rau củ', 25000, 40, 0, 'hanhlyson.jpg'],
            ['Chanh không hạt - 500 g', 'Trái cây', 22000, 55, 0, 'chanhkhonghat.png'],
            ['Dưa hấu - quả 2 kg', 'Trái cây', 48000, 20, 0.10, 'duahau.jpg'],
            ['Thịt lợn vai - 500 g', 'Thịt tươi', 65000, 25, 0, 'thitlonvai.jpg'],
            ['Cá basa - 500 g', 'Hải sản', 45000, 35, 0.05, 'cabasa.jpg'],
        ];
        foreach ($products as $product) {
            $path = 'demo/'.$product[5];
            if (! Storage::disk('public')->exists($path)) {
                if (! Storage::disk('public')->put($path, file_get_contents(public_path('assets/img/'.$product[5])))) {
                    throw new RuntimeException('Unable to copy demo image: '.$path);
                }
            }
        }
        DB::transaction(function () use ($products, $password) {
            User::firstOrCreate(['email' => 'customer@greenfood.test'], [
                'name' => 'Nguyễn Minh Anh', 'password' => Hash::make('GreenFood-demo-2026'),
                'Role' => 0, 'Status' => 1,
            ]);
            // Do not reset credentials or promote any existing account on repeated seeds.
            if (! User::where('email', 'admin@greenfood.test')->exists()) {
                User::create([
                    'email' => 'admin@greenfood.test', 'name' => 'GreenFood Demo Admin',
                    'password' => Hash::make($password), 'Role' => 1, 'Status' => 1,
                ]);
            }
            foreach ($products as [$name, $category, $price, $stock, $discount, $image]) {
                Product::firstOrCreate(['ProductName' => $name], [
                    'Description' => $name.'. Sản phẩm mẫu GreenFood; bảo quản theo loại thực phẩm.',
                    'Category' => $category, 'Price' => $price, 'Quantity' => $stock,
                    'Discount' => $discount, 'Image' => 'demo/'.$image,
                ]);
            }
        });
    }
}
