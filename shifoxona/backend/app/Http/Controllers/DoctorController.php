<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class DoctorController extends Controller
{
    public function index(Request $request)
    {
        $request->validate(['search' => 'nullable|string|max:100', 'hospital_id' => 'nullable|integer', 'page' => 'nullable|integer|min:1']);

        $query = User::where('role', 'doctor')
            ->with('hospital:id,name')
            ->withCount('patients');

        if ($request->user()->role === 'director') {
            $query->where('hospital_id', $request->user()->hospital_id ?? 0);
        }
        if ($request->filled('hospital_id')) {
            $query->where('hospital_id', $request->integer('hospital_id'));
        }
        if ($request->filled('search')) {
            $search = '%'.$request->input('search').'%';
            $query->where(function ($query) use ($search) {
                $query->where('name', 'like', $search)
                    ->orWhere('email', 'like', $search);
            });
        }

        return $query->orderBy('name')->paginate(10);
    }

    public function show(Request $request, User $staff)
    {
        $this->checkScope($request, $staff);

        return $staff->load('hospital.region', 'hospital.district')->loadCount('patients');
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['role'] = 'doctor';
        $doctor = User::create($data);

        return response()->json($doctor->load('hospital'), 201);
    }

    public function update(Request $request, User $staff)
    {
        $this->checkScope($request, $staff);
        $data = $this->validated($request, $staff);
        if ($staff->hospital_id !== (int) $data['hospital_id'] && $staff->patients()->exists()) {
            throw ValidationException::withMessages(['hospital_id' => 'Bemorlari mavjud shifokorni boshqa shifoxonaga o‘tkazib bo‘lmaydi.']);
        }
        $staff->update($data);

        return $staff->load('hospital');
    }

    public function destroy(Request $request, User $staff)
    {
        $this->checkScope($request, $staff);
        if ($staff->patients()->exists()) {
            throw ValidationException::withMessages(['staff' => 'Shifokorga biriktirilgan bemorlar mavjud. Avval bemor kartalarini boshqaring.']);
        }
        DB::table('sessions')->where('user_id', $staff->id)->delete();
        $staff->delete();

        return response()->noContent();
    }

    private function checkScope(Request $request, User $staff): void
    {
        abort_unless($staff->role === 'doctor', 404);
        if ($request->user()->role === 'admin') {
            return;
        }

        $isOwnDoctor = $request->user()->role === 'director'
            && $request->user()->hospital_id === $staff->hospital_id
            && $staff->role === 'doctor';

        abort_unless($isOwnDoctor, 403, 'Bu xodimni boshqarishga ruxsat yo‘q.');
    }

    private function validated(Request $request, ?User $staff = null): array
    {
        $request->merge(['email' => strtolower(trim((string) $request->input('email')))]);
        if ($request->user()->role === 'director') {
            $request->merge(['hospital_id' => $request->user()->hospital_id]);
        }
        $data = $request->validate([
            'name' => 'required|string|max:150', 'email' => ['required', 'email', 'max:150', Rule::unique('users')->ignore($staff?->id)],
            'password' => [$staff ? 'nullable' : 'required', 'string', 'min:8', 'max:72'],
            'hospital_id' => 'required|integer|exists:hospitals,id', 'phone' => 'nullable|string|max:30',
            'specialization' => 'required|string|max:150',
        ]);
        if (empty($data['password'])) {
            unset($data['password']);
        }

        return $data;
    }
}
