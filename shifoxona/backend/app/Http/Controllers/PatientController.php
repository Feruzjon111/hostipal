<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use App\Models\User;
use App\Support\Visibility;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class PatientController extends Controller
{
    private const RELATIONS = ['doctor:id,name,specialization', 'hospital:id,name', 'region', 'district', 'disease'];

    public function index(Request $request)
    {
        $filters = $request->validate([
            'search' => 'nullable|string|max:100', 'status' => ['nullable', Rule::in(['waiting', 'in_progress', 'completed'])],
            'disease_id' => 'nullable|integer|exists:diseases,id', 'doctor_id' => 'nullable|integer',
            'hospital_id' => 'nullable|integer', 'date' => 'nullable|date_format:Y-m-d', 'page' => 'nullable|integer|min:1',
        ]);
        $query = Visibility::patients($request->user())->with(self::RELATIONS);
        if (! empty($filters['search'])) {
            $search = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $filters['search']).'%';
            $query->where(function ($query) use ($search) {
                $query->where('first_name', 'like', $search)
                    ->orWhere('last_name', 'like', $search)
                    ->orWhere('middle_name', 'like', $search)
                    ->orWhere('passport', 'like', $search)
                    ->orWhere('pin', 'like', $search);
            });
        }
        foreach (['status', 'disease_id', 'doctor_id', 'hospital_id'] as $key) {
            if (! empty($filters[$key])) {
                $query->where($key, $filters[$key]);
            }
        }
        if (! empty($filters['date'])) {
            $query->whereDate('registered_at', $filters['date']);
        }

        return $query->orderByDesc('registered_at')->orderByDesc('id')->paginate(10);
    }

    public function show(Patient $patient)
    {
        Gate::authorize('view', $patient);

        return $patient->load(self::RELATIONS);
    }

    public function store(Request $request)
    {
        Gate::authorize('create', Patient::class);
        $data = $this->validated($request);

        return response()->json(Patient::create($data)->load(self::RELATIONS), 201);
    }

    public function update(Request $request, Patient $patient)
    {
        Gate::authorize('update', $patient);
        $patient->update($this->validated($request, $patient));

        return $patient->load(self::RELATIONS);
    }

    public function destroy(Patient $patient)
    {
        Gate::authorize('delete', $patient);
        $patient->delete();

        return response()->noContent();
    }

    private function validated(Request $request, ?Patient $patient = null): array
    {
        $request->merge(['passport' => strtoupper(trim((string) $request->input('passport')))]);
        if ($request->user()->role === 'doctor') {
            $doctor = $request->user();
        } else {
            $request->validate(['doctor_id' => ['required', 'integer', Rule::exists('users', 'id')->where('role', 'doctor')]]);
            $doctor = User::findOrFail($request->integer('doctor_id'));
        }
        abort_unless($doctor->hospital_id, 422, 'Shifokor shifoxonaga biriktirilmagan.');
        $data = $request->validate([
            'first_name' => 'required|string|max:100', 'last_name' => 'required|string|max:100', 'middle_name' => 'required|string|max:100',
            'passport' => ['required', 'regex:/^[A-Z]{2}[0-9]{7}$/', Rule::unique('patients')->where('doctor_id', $doctor->id)->ignore($patient?->id)],
            'pin' => ['required', 'string', 'regex:/^[0-9]{14}$/', Rule::unique('patients')->where('doctor_id', $doctor->id)->ignore($patient?->id)],
            'region_id' => 'required|integer|exists:regions,id',
            'district_id' => ['required', 'integer', Rule::exists('districts', 'id')->where('region_id', $request->input('region_id'))],
            'address' => 'required|string|max:500', 'disease_id' => 'required|integer|exists:diseases,id',
            'registered_at' => 'required|date_format:Y-m-d\TH:i', 'status' => ['required', Rule::in(['waiting', 'in_progress', 'completed'])],
            'notes' => 'nullable|string|max:5000',
        ]);

        $data['doctor_id'] = $doctor->id;
        $data['hospital_id'] = $doctor->hospital_id;

        return $data;
    }
}
