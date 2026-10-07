<?php

namespace Database\Seeders;

use App\Models\Disease;
use App\Models\District;
use App\Models\Region;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ReferenceSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            foreach (json_decode(file_get_contents(__DIR__.'/data/regions.json'), true, 512, JSON_THROW_ON_ERROR) as $region) {
                Region::updateOrCreate(['id' => $region['id']], ['name' => $region['name_uz']]);
            }
            foreach (json_decode(file_get_contents(__DIR__.'/data/districts.json'), true, 512, JSON_THROW_ON_ERROR) as $district) {
                District::updateOrCreate(['id' => $district['id']], ['region_id' => $district['region_id'], 'name' => $district['name_uz']]);
            }
            foreach (['O‘tkir respirator infeksiya', 'Arterial gipertoniya', 'Migren', 'Bronxit', 'Gastrit', 'Allergiya', 'Dermatit', 'Qandli diabet', 'Kamqonlik', 'Artrit', 'Otit', 'Pnevmoniya'] as $index => $name) {
                Disease::updateOrCreate(['id' => $index + 1], ['name' => $name]);
            }
        });
    }
}
