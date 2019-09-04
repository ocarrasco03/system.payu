<?php

use Illuminate\Database\Seeder;
use App\Garflo\Models\System;

class SystemsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $system = System::create([
            'label' => 'System Online',
            'status' => true,
        ]);

        $viagenza = System::create([
            'label' => 'Viagenza',
            'status' => false,
        ]);
    }
}
