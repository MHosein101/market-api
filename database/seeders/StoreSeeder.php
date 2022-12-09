<?php

namespace Database\Seeders;

use App\Models\Store;
use Illuminate\Database\Seeder;
use App\Http\Helpers\SeederHelper;

class StoreSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $rp = random_int(7, 15);

        $wordsName = ['ماهان', 'سپهر', 'کوروش', 'دایجی کاولا', 'بیوتی رول', 'تکنو', ' لایف', 'دایلی', 'مات'];
        $startName = 'فروشگاه';

        $wordsProvince = ['مازندران', 'تهران', 'اراک', 'گیلان', 'تبریز', 'سمنان', 'خراسان', 'اصفهان', 'شیراز', 'کرمان'];

        // for($i = 0; $i < $rp; $i++) {

            $n = SeederHelper::string($wordsName, [2, null], $startName);
            $s = SeederHelper::string($wordsName, [2, null], $startName, '-');
            $ec = SeederHelper::number(6);
            $opn = SeederHelper::number(11);

            $p = SeederHelper::one($wordsProvince);

            Store::create([
                'name' => $n ,
                'slug' => $s ,
                'economic_code' => $ec ,
        
                'owner_full_name' => 'شخصی ناشناس' ,
                'owner_phone_number' => $opn ,
        
                'province' => $p ,
                'city' => $p ,

                'office_address' => '' ,
                'office_number'  => '' ,
                
                'logo_image' => request()->getSchemeAndHttpHost() . '/default.jpg' ,
        
                'admin_confirmed' => time() ,
            ]);

        // }
    }
}
