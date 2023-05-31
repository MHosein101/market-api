<?php

namespace Database\Seeders;

use App\Http\Helpers\SeederHelper;
use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // $rp = random_int(40, 50);
        $words = ['ماشین', 'گلابی', 'لباس', 'سیب', 'گوشی', 's2', 'e14', 'xxl', 'upq', 'درجه یک'];
        $start = 'محصول';

        for($i = 0; $i < 11; $i++) 
        {
            $t = SeederHelper::string($words, [4, 5], $start);
            $s = SeederHelper::string($words, [4, 5], $start, '-');
            $bc = SeederHelper::number(12);

            Product::create([
                'title'       => $t ,
                'slug'        => $s , 
                'barcode'     => $bc , 
                'description' => 'توضیحاتی برای این محصول با این عنوان که در تصمیم گیری برای خرید کاربر کمک میکند' , 
                'brand_id'    => 0
            ]);

        }
    }

}
