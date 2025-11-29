<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class GeneroSeeder extends Seeder
{
    public function run(): void
    {
        \Database\Factories\GeneroFactory::new()->count(15)->create();
        
    }
}
