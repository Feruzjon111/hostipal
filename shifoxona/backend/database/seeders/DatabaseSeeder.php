<?php

namespace Database\Seeders;

use App\Models\Disease;
use App\Models\District;
use App\Models\Hospital;
use App\Models\Patient;
use App\Models\Region;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(ReferenceSeeder::class);

        // Production bazasiga faqat viloyat, tuman va kasalliklar yoziladi.
        if (app()->environment('production')) {
            return;
        }

        DB::transaction(function () {
            $tashkent = Region::where('name', 'Toshkent shahri')->firstOrFail();
            $samarkand = Region::where('name', 'Samarqand viloyati')->firstOrFail();
            $hospitals = [
                Hospital::firstOrCreate(['tin' => '200100101'], ['name' => 'Shifo Nur tibbiyot markazi', 'region_id' => $tashkent->id, 'district_id' => $this->district($tashkent, 'Yunusobod tumani')->id]),
                Hospital::firstOrCreate(['tin' => '200100102'], ['name' => 'Samarqand markaziy shifoxonasi', 'region_id' => $samarkand->id, 'district_id' => $this->district($samarkand, 'Samarqand')->id]),
            ];
            $password = Hash::make('Password123!');
            foreach ([1, 2] as $i) {
                User::firstOrCreate(['email' => "admin{$i}@shifo.uz"], ['name' => $i === 1 ? 'Azizbek Karimov' : 'Nodira Sobirova', 'password' => $password, 'role' => 'admin']);
                User::firstOrCreate(['email' => "director{$i}@shifo.uz"], ['name' => $i === 1 ? 'Dilshod Rahimov' : 'Malika Usmonova', 'password' => $password, 'role' => 'director', 'hospital_id' => $hospitals[$i - 1]->id, 'phone' => '+998 90 123 45 6'.$i]);
                $doctor = User::firstOrCreate(['email' => "doctor{$i}@shifo.uz"], ['name' => $i === 1 ? 'Shahnoza Aliyeva' : 'Javohir Ismoilov', 'password' => $password, 'role' => 'doctor', 'hospital_id' => $hospitals[$i - 1]->id, 'phone' => '+998 91 765 43 2'.$i, 'specialization' => $i === 1 ? 'Terapevt' : 'Kardiolog']);
                $firstNames = ['Abdulloh', 'Madina', 'Sardor', 'Zilola', 'Bekzod', 'Diyora', 'Jasur', 'Shahzoda', 'Oybek', 'Farangiz', 'Akmal', 'Sevara'];
                $lastNames = ['Tursunov', 'Ahmedova', 'Sodiqov', 'Karimova', 'Yusupov', 'Rustamova', 'Nazarov', 'Qodirova', 'Abdullayev', 'Mirzayeva', 'Hasanov', 'Ergasheva'];
                foreach ($firstNames as $n => $firstName) {
                    $number = $i * 100 + $n;
                    Patient::firstOrCreate(['doctor_id' => $doctor->id, 'passport' => 'AA'.str_pad((string) $number, 7, '0', STR_PAD_LEFT)], [
                        'hospital_id' => $doctor->hospital_id, 'first_name' => $firstName, 'last_name' => $lastNames[$n],
                        'middle_name' => $n % 2 === 0 ? 'Anvar o‘g‘li' : 'Anvar qizi', 'pin' => str_pad((string) (30000000 + $number), 14, '0', STR_PAD_LEFT),
                        'region_id' => $hospitals[$i - 1]->region_id, 'district_id' => $hospitals[$i - 1]->district_id,
                        'address' => 'Mustaqillik ko‘chasi, '.($n + 10).'-uy', 'disease_id' => Disease::orderBy('id')->skip($n % 6)->first()->id,
                        'registered_at' => now()->subDays($n < 4 ? 0 : $n % 7)->startOfDay()->addHours(9)->addMinutes($n * 20),
                        'status' => ['waiting', 'in_progress', 'completed'][$n % 3], 'notes' => 'Namuna bemor kartasi. Barcha shaxsiy ma’lumotlar sun’iy.',
                    ]);
                }
            }
        });
    }

    private function district(Region $region, string $name): District
    {
        return District::where('region_id', $region->id)->where('name', $name)->firstOrFail();
    }
}
