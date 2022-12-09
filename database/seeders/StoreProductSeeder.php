<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Store;
use App\Models\StoreProduct;
use Illuminate\Database\Seeder;

class StoreProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $products = Product::get();
        $stores = Store::get();

        foreach($products as $p) {
            foreach($stores as $s) {

                $pp = floor( random_int(100000, 1500000) / 1000 ) * 1000;
                $sp = $pp + floor( random_int(10000, 150000) / 100 ) * 100;;
                $pud = time() - random_int(1800, 3600 * 24 * 14);

                StoreProduct::create([
                    'production_price' => $pp ,
                    'store_price' => $sp ,
                    'consumer_price' => $sp ,

                    'price_update_time' => $pud ,
                    'warehouse_count' => random_int(0, 25) ,

                    'admin_confirmed' => $pud + random_int(1000, 3000) ,
                    'store_id' => $s->id ,
                    'product_id' => $p->id
                ]);
            }
        }
    }
}
