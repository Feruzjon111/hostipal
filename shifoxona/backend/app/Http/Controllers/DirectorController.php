<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class DirectorController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'search' => 'nullable|string|max:100',
            'hospital_id' => 'nullable|integer',
            'page' => 'nullable|integer|min:1',
        ]);

        $query = User::where('role', 'director')->with('hospital:id,name');

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

    public function show(User $staff)
    {
        abort_unless($staff->role === 'director', 404);

        return $staff->load('hospital.region', 'hospital.district');
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['role'] = 'director';
        $director = User::create($data);

        return response()->json($director->load('hospital'), 201);
    }

    public function update(Request $request, User $staff)
    {
        abort_unless($staff->role === 'director', 404);
        $staff->update($this->validated($request, $staff));

        return $staff->load('hospital');
    }

    public function destroy(User $staff)
    {
        abort_unless($staff->role === 'director', 404);
        DB::table('sessions')->where('user_id', $staff->id)->delete();
        $staff->delete();

        return response()->noContent();
    }

    private function validated(Request $request, ?User $staff = null): array
    {
        $request->merge(['email' => strtolower(trim((string) $request->input('email')))]);

        $data = $request->validate([
            'name' => 'required|string|max:150',
            'email' => ['required', 'email', 'max:150', Rule::unique('users')->ignore($staff?->id)],
            'password' => [$staff ? 'nullable' : 'required', 'string', 'min:8', 'max:72'],
            'hospital_id' => 'required|integer|exists:hospitals,id',
            'phone' => 'nullable|string|max:30',
        ]);

        if (empty($data['password'])) {
            unset($data['password']);
        }

        return $data;
    }
}
