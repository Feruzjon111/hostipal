<?php

namespace Tests\Feature;

use App\Models\Disease;
use App\Models\District;
use App\Models\Hospital;
use App\Models\Patient;
use App\Models\Region;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class HospitalSystemTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    private function account(string $role, int $n = 1): User
    {
        return User::where('email', "{$role}{$n}@shifo.uz")->firstOrFail();
    }

    private function payload(array $extra = []): array
    {
        $hospital = Hospital::where('tin', '200100101')->firstOrFail();

        return array_replace([
            'first_name' => 'Test', 'last_name' => 'Bemor', 'middle_name' => 'Anvar o‘g‘li',
            'passport' => 'AB1234567', 'pin' => '01234567890123', 'region_id' => $hospital->region_id,
            'district_id' => $hospital->district_id, 'address' => 'Test ko‘chasi, 1-uy',
            'disease_id' => Disease::first()->id, 'registered_at' => now()->format('Y-m-d\TH:i'),
            'status' => 'waiting', 'notes' => 'Test izohi',
        ], $extra);
    }

    public function test_seeder_provides_two_users_per_role_and_is_repeatable(): void
    {
        $this->seed();
        foreach (['admin', 'director', 'doctor'] as $role) {
            $this->assertSame(2, User::where('role', $role)->count());
        }
        $this->assertSame(14, Region::count());
        $this->assertGreaterThan(200, District::count());
        $this->assertSame(12, Disease::count());
        $this->assertSame(2, Hospital::count());
        $this->assertSame(24, Patient::count());
        $this->assertTrue(Hash::check('Password123!', $this->account('doctor')->password));
    }

    public function test_guests_cannot_access_any_patient_or_management_data(): void
    {
        foreach (['me', 'dashboard', 'lookups', 'patients', 'patients/1', 'doctors', 'hospitals', 'directors'] as $path) {
            $this->getJson('/api/'.$path)->assertUnauthorized();
        }
        $this->postJson('/api/patients', $this->payload())->assertUnauthorized();
    }

    public function test_login_regenerates_session_and_logout_revokes_access(): void
    {
        $response = $this->postJson('/api/login', ['email' => 'DOCTOR1@SHIFO.UZ', 'password' => 'Password123!']);
        $response->assertOk()->assertJsonPath('user.role', 'doctor')->assertJsonStructure(['csrf_token', 'user' => ['hospital']]);
        $this->assertArrayNotHasKey('password', $response->json('user'));
        $this->getJson('/api/me')->assertOk();
        $this->postJson('/api/logout')->assertOk();
        $this->getJson('/api/me')->assertUnauthorized();
    }

    public function test_wrong_password_is_rejected_and_login_is_rate_limited(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/login', ['email' => 'doctor1@shifo.uz', 'password' => 'wrong'])->assertUnprocessable()->assertJsonValidationErrors('email');
        }
        $this->postJson('/api/login', ['email' => 'doctor1@shifo.uz', 'password' => 'wrong'])->assertStatus(429);
    }

    public function test_each_doctor_sees_only_own_patients_and_scoped_dashboard(): void
    {
        foreach ([1, 2] as $n) {
            $doctor = $this->account('doctor', $n);
            $this->actingAs($doctor)->getJson('/api/patients')->assertOk()->assertJsonPath('total', 12);
            foreach ($this->getJson('/api/patients')->json('data') as $patient) {
                $this->assertSame($doctor->id, $patient['doctor_id']);
            }
            $this->getJson('/api/dashboard')->assertOk()->assertJsonPath('total', 12)->assertJsonPath('doctors', 1);
            $this->getJson('/api/lookups')->assertJsonCount(1, 'doctors')->assertJsonCount(1, 'hospitals');
        }
    }

    public function test_doctor_cannot_read_update_or_delete_another_doctors_patient(): void
    {
        $other = $this->account('doctor', 2)->patients()->first();
        $this->actingAs($this->account('doctor'));
        $this->getJson('/api/patients/'.$other->id)->assertForbidden();
        $this->putJson('/api/patients/'.$other->id, $this->payload())->assertForbidden();
        $this->deleteJson('/api/patients/'.$other->id)->assertForbidden();
        $this->assertDatabaseHas('patients', ['id' => $other->id]);
    }

    public function test_doctor_can_complete_patient_crud_and_cannot_spoof_ownership(): void
    {
        $doctor = $this->account('doctor');
        $other = $this->account('doctor', 2);
        $this->actingAs($doctor);
        $response = $this->postJson('/api/patients', $this->payload(['doctor_id' => $other->id, 'hospital_id' => $other->hospital_id]));
        $response->assertCreated()->assertJsonPath('doctor_id', $doctor->id)->assertJsonPath('hospital_id', $doctor->hospital_id)->assertJsonPath('pin', '01234567890123');
        $id = $response->json('id');
        $this->getJson('/api/patients/'.$id)->assertOk()->assertJsonPath('disease.name', Disease::first()->name);
        $this->putJson('/api/patients/'.$id, $this->payload(['status' => 'completed']))->assertOk()->assertJsonPath('status', 'completed');
        $this->deleteJson('/api/patients/'.$id)->assertNoContent();
        $this->assertDatabaseMissing('patients', ['id' => $id]);
    }

    public function test_passport_pin_required_fields_and_status_are_validated(): void
    {
        $this->actingAs($this->account('doctor'))->postJson('/api/patients', $this->payload([
            'passport' => '123AB4567', 'pin' => '1234567890123', 'first_name' => '', 'status' => 'unknown', 'registered_at' => '2026-02-31T09:00',
        ]))->assertUnprocessable()->assertJsonValidationErrors(['passport', 'pin', 'first_name', 'status', 'registered_at']);
    }

    public function test_district_must_belong_to_selected_region(): void
    {
        $different = District::where('region_id', '!=', $this->payload()['region_id'])->first();
        $this->actingAs($this->account('doctor'))->postJson('/api/patients', $this->payload(['district_id' => $different->id]))->assertUnprocessable()->assertJsonValidationErrors('district_id');
    }

    public function test_duplicate_documents_for_same_doctor_are_rejected_but_other_doctors_can_register(): void
    {
        $this->actingAs($this->account('doctor'))->postJson('/api/patients', $this->payload())->assertCreated();
        $this->postJson('/api/patients', $this->payload())->assertUnprocessable()->assertJsonValidationErrors(['passport', 'pin']);
        $this->actingAs($this->account('doctor', 2))->postJson('/api/patients', $this->payload())->assertCreated();
    }

    public function test_search_filters_pagination_and_dashboard_counts_match(): void
    {
        $this->actingAs($this->account('admin'));
        $this->getJson('/api/patients?search=AA0000100')->assertOk()->assertJsonPath('total', 1)->assertJsonPath('data.0.first_name', 'Abdulloh');
        $this->getJson('/api/patients?status=waiting')->assertJsonPath('total', 8);
        $this->getJson('/api/patients?page=3')->assertJsonCount(4, 'data');
        $hospital = $this->account('director')->hospital_id;
        $this->getJson('/api/patients?hospital_id='.$hospital)->assertJsonPath('total', 12);
        $this->getJson('/api/dashboard')->assertJsonPath('total', 24)->assertJsonPath('waiting', 8)->assertJsonPath('completed', 8);
    }

    public function test_director_can_read_only_own_hospital_patients_but_cannot_modify_them(): void
    {
        $director = $this->account('director');
        $own = Patient::where('hospital_id', $director->hospital_id)->first();
        $other = $this->account('doctor', 2)->patients()->first();
        $this->actingAs($director)->getJson('/api/patients')->assertJsonPath('total', 12);
        $this->getJson('/api/patients/'.$own->id)->assertOk();
        $this->getJson('/api/patients/'.$other->id)->assertForbidden();
        $this->postJson('/api/patients', $this->payload())->assertForbidden();
        $this->putJson('/api/patients/'.$own->id, $this->payload())->assertForbidden();
        $this->deleteJson('/api/patients/'.$own->id)->assertForbidden();
    }

    public function test_director_doctor_crud_forces_own_hospital_and_doctor_role(): void
    {
        $director = $this->account('director');
        $this->actingAs($director);
        $payload = ['name' => 'Yangi Shifokor', 'email' => 'new@shifo.uz', 'password' => 'NewPassword!', 'specialization' => 'Terapevt', 'hospital_id' => $this->account('doctor', 2)->hospital_id, 'role' => 'admin'];
        $response = $this->postJson('/api/doctors', $payload)->assertCreated()->assertJsonPath('role', 'doctor')->assertJsonPath('hospital_id', $director->hospital_id);
        $id = $response->json('id');
        $this->assertTrue(Hash::check('NewPassword!', User::find($id)->password));
        $this->putJson('/api/doctors/'.$id, [...$payload, 'password' => '', 'name' => 'O‘zgargan Shifokor'])->assertOk()->assertJsonPath('name', 'O‘zgargan Shifokor');
        $this->assertTrue(Hash::check('NewPassword!', User::find($id)->password));
        $this->deleteJson('/api/doctors/'.$id)->assertNoContent();
    }

    public function test_director_cannot_manage_other_hospital_staff_or_create_directors(): void
    {
        $other = $this->account('doctor', 2);
        $this->actingAs($this->account('director'))->getJson('/api/doctors')->assertJsonPath('total', 1);
        $this->getJson('/api/doctors/'.$other->id)->assertForbidden();
        $this->putJson('/api/doctors/'.$other->id, [])->assertForbidden();
        $this->deleteJson('/api/doctors/'.$other->id)->assertForbidden();
        $this->getJson('/api/directors')->assertForbidden();
        $this->postJson('/api/directors', [])->assertForbidden();
    }

    public function test_doctor_cannot_access_staff_or_hospital_management(): void
    {
        $this->actingAs($this->account('doctor'));
        foreach (['doctors', 'hospitals', 'directors'] as $resource) {
            $this->getJson('/api/'.$resource)->assertForbidden();
            $this->postJson('/api/'.$resource, [])->assertForbidden();
        }
    }

    public function test_doctor_with_patients_cannot_be_deleted_or_transferred(): void
    {
        $doctor = $this->account('doctor');
        $this->actingAs($this->account('admin'))->deleteJson('/api/doctors/'.$doctor->id)->assertUnprocessable();
        $this->putJson('/api/doctors/'.$doctor->id, ['name' => $doctor->name, 'email' => $doctor->email, 'specialization' => $doctor->specialization, 'hospital_id' => $this->account('doctor', 2)->hospital_id])->assertUnprocessable()->assertJsonValidationErrors('hospital_id');
    }

    public function test_admin_sees_all_data_and_can_assign_patients_to_another_doctor(): void
    {
        $other = $this->account('doctor', 2);
        $this->actingAs($this->account('admin'))->getJson('/api/patients')->assertJsonPath('total', 24);
        $this->getJson('/api/hospitals')->assertJsonPath('total', 2);
        $this->getJson('/api/doctors')->assertJsonPath('total', 2);
        $response = $this->postJson('/api/patients', $this->payload(['doctor_id' => $other->id, 'hospital_id' => 999]))->assertCreated()->assertJsonPath('hospital_id', $other->hospital_id);
        $this->deleteJson('/api/patients/'.$response->json('id'))->assertNoContent();
    }

    public function test_hospital_crud_validates_tin_and_restricts_nonempty_deletion(): void
    {
        $this->actingAs($this->account('admin'));
        $geo = $this->payload();
        $payload = ['name' => 'Yangi shifoxona', 'region_id' => $geo['region_id'], 'district_id' => $geo['district_id'], 'tin' => '123456789'];
        $this->postJson('/api/hospitals', [...$payload, 'tin' => '123'])->assertUnprocessable()->assertJsonValidationErrors('tin');
        $id = $this->postJson('/api/hospitals', $payload)->assertCreated()->json('id');
        $this->putJson('/api/hospitals/'.$id, [...$payload, 'name' => 'Yangilangan shifoxona'])->assertOk()->assertJsonPath('name', 'Yangilangan shifoxona');
        $this->getJson('/api/hospitals/'.$id)->assertOk()->assertJsonCount(0, 'directors');
        $this->deleteJson('/api/hospitals/'.$id)->assertNoContent();
        $this->deleteJson('/api/hospitals/'.$this->account('doctor')->hospital_id)->assertUnprocessable();
    }

    public function test_director_can_edit_own_hospital_but_not_other_hospitals(): void
    {
        $director = $this->account('director');
        $hospital = $director->hospital;
        $other = $this->account('doctor', 2)->hospital_id;
        $this->actingAs($director)->getJson('/api/hospitals')->assertJsonPath('total', 1);
        $this->getJson('/api/hospitals/'.$other)->assertForbidden();
        $this->putJson('/api/hospitals/'.$other, [])->assertForbidden();
        $this->putJson('/api/hospitals/'.$hospital->id, $hospital->only(['name', 'region_id', 'district_id', 'tin']))->assertOk();
        $this->postJson('/api/hospitals', [])->assertForbidden();
        $this->deleteJson('/api/hospitals/'.$hospital->id)->assertForbidden();
    }

    public function test_admin_can_manage_directors_and_cannot_use_doctor_route_to_modify_admins(): void
    {
        $this->actingAs($this->account('admin'));
        $payload = ['name' => 'Yangi Rahbar', 'email' => 'new-director@shifo.uz', 'password' => 'NewPassword!', 'hospital_id' => Hospital::first()->id, 'phone' => '+998901234567'];
        $id = $this->postJson('/api/directors', $payload)->assertCreated()->assertJsonPath('role', 'director')->json('id');
        $this->getJson('/api/directors/'.$id)->assertOk();
        $this->putJson('/api/directors/'.$id, [...$payload, 'name' => 'Rahbar Test'])->assertOk();
        $this->deleteJson('/api/directors/'.$id)->assertNoContent();
        $this->deleteJson('/api/doctors/'.$this->account('admin', 2)->id)->assertNotFound();
    }
}
