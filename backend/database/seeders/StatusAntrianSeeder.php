<?php

namespace Database\Seeders;

use App\Models\StatusAntrian;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class StatusAntrianSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $statuses = [
            ['id' => 1, 'name' => 'Waiting'],
            ['id' => 2, 'name' => 'Called'],
            ['id' => 3, 'name' => 'On Going'],
            ['id' => 3, 'name' => 'Done'],
        ];

        foreach ($statuses as $status) {
            StatusAntrian::updateOrCreate(['id' => $status['id']], $status);
        }
    }
}
