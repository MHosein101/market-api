<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\UserAccountType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        User::create([
            'account_type' => UserAccountType::Admin ,
            'phone_number_primary' => '09364221051' ,
            'national_code' => '2082082080' ,
            'full_name' => 'Admin Hosein marzban' ,
        ]);

        User::create([
            'account_type' => UserAccountType::Admin ,
            'phone_number_primary' => '09302520508' ,
            'national_code' => '2092092090' ,
            'full_name' => 'Admin Ali HassanZadeh'
        ]);
        
    }
}
