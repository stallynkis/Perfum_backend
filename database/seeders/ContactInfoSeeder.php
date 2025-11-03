<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\ContactInfo;

class ContactInfoSeeder extends Seeder
{
   
    public function run(): void
    {
        ContactInfo::create([
           
            'is_active' => true
        ]);
    }
}
