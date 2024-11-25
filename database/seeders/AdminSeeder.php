<?php

namespace Database\Seeders;

use App\Http\Controllers\Service;
use App\Models\Person;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::beginTransaction();
        try {
            // Insérer dans la table `users`
            $userId = DB::table('users')->insertGetId([
                'email' => 'ayenaaurel15@gmail.com',
                'phone' => null,
                'email_verified_at' => now(),
                'password' => Hash::make('P@ssword123'),
                'uid' =>Str::uuid(),
                'is_verified' => true,
                'enabled' => true,
                'connected' => true,
                'last_ip_login' => '127.0.0.1',
                'last_login' => null,
                'deleted' => false,
                'google_id' => null,
                'social_type' => null,
                'code' => null,
                'code_user' => (new Service())->generateRandomAlphaNumeric(7,(new User()),'code_user'),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Insérer dans la table `person`
            $personId = DB::table('person')->insertGetId([
                'first_name' => 'FirstName',
                'last_name' => 'LastName',   
                'user_id' => $userId,
                'country_id' => null,
                'connected' => false,
                'sex' => true,
                'dateofbirth' => null,
                'profile_img_code' => (new Service())->generateRandomAlphaNumeric(7,(new Person()),'profile_img_code'),
                'first_login' => true,
                'phonenumber' => null,
                'deleted' => false,
                'uid' =>Str::uuid(),
                'type' => 'client',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Insérer dans la table `clients`
            DB::table('clients')->insert([
                'person_id' => $personId,
                'is_merchant' => false,
                'is_deliverer' => false,
                'uid' =>Str::uuid(),
                'deleted' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Insérer dans la table `admin`
            DB::table('admin')->insert([
                'person_id' => $personId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::commit();

            echo "Seeding completed successfully!\n";
        } catch (\Exception $e) {
            DB::rollBack();
            echo "Seeding failed: " . $e->getMessage() . "\n";
        }
    }
}
