<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            foreach (['cedar' => 'Cedar Valley School', 'willow' => 'Willow Grove School'] as $slug => $name) {
                if (DB::table('schools')->where('name', $name)->exists()) {
                    continue;
                }
                $school = DB::table('schools')->insertGetId(['name' => $name]);
                foreach (['accountant', 'parent', 'parent2'] as $login) {
                    $role = $login === 'accountant' ? 'accountant' : 'parent';
                    $user = DB::table('users')->insertGetId([
                        'school_id' => $school, 'name' => ucfirst($login),
                        'role' => $role, 'email' => "$login@$slug.test",
                        'password' => Hash::make('SchoolDemo2026!'),
                    ]);
                    if ($role === 'parent') {
                        DB::table('accounts')->insert([
                            'school_id' => $school, 'parent_id' => $user,
                            'student' => ($slug === 'cedar' ? ['Mina Shah', 'Ravi Noor'] : ['Tara Roy', 'Arun Das'])[$login === 'parent' ? 0 : 1],
                            'fee_paise' => $login === 'parent' ? 1200000 : 960000,
                        ]);
                    }
                }
            }
        });
    }
};
