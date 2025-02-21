<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Person;

class PersonSeeder extends Seeder
{
    public function run()
    {
        Person::insert([
            ['name' => 'John Doe', 'email' => 'john@example.com', 'role' => 'Project Manager'],
            ['name' => 'Jane Smith', 'email' => 'jane@example.com', 'role' => 'Team Lead'],
            ['name' => 'Alice Johnson', 'email' => 'alice@example.com', 'role' => 'Developer'],
        ]);
    }
}
