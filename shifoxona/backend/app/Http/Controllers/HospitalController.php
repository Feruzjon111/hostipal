<?php

namespace App\Http\Controllers;

use App\Models\Hospital;
use App\Support\Visibility;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class HospitalController extends Controller
{
    private const RELATIONS = ['region', 'district', 'directors:id,name,email,phone,hospital_id'];

    public function index(Request $request)
    {
        $request->validate(['search' => 'nullable|string|max:100', 'page' => 'nullable|integer|min:1']);

        $query = Visibility::hospitals($request->user())
            ->with(self::RELATIONS)
            ->withCount(['doctors', 'patients']);

        if ($request->filled('search')) {
            $query->where('name', 'like', '%'.$request->input('search').'%');
        }

        return $query->orderBy('name')->paginate(10);
    }

    public function show(Request $request, Hospital $hospital)
    {
        $this->checkScope($request, $hospital);

        return $hospital->load(self::RELATIONS)->loadCount(['doctors', 'patients']);
    }

    public function store(Request $request)
    {
        abort_unless($request->user()->role === 'admin', 403);

        return response()->json(Hospital::create($this->validated($request))->load(self::RELATIONS), 201);
    }

    public function update(Request $request, Hospital $hospital)
    {
        $this->checkScope($request, $hospital);
        $hospital->update($this->validated($request, $hospital));

        return $hospital->load(self::RELATIONS);
    }

    public function destroy(Request $request, Hospital $hospital)
    {
        abort_unless($request->user()->role === 'admin', 403);
        if ($hospital->users()->exists() || $hospital->patients()->exists()) {
            throw ValidationException::withMessages(['hospital' => 'Shifoxonada xodimlar yoki bemorlar mavjud. Bo‘sh shifoxonani o‘chirish mumkin.']);
        }
        $hospital->delete();

        return response()->noContent();
    }

    private function checkScope(Request $request, Hospital $hospital): void
    {
        abort_unless($request->user()->role === 'admin' || $request->user()->hospital_id === $hospital->id, 403);
    }

    private function validated(Request $request, ?Hospital $hospital = null): array
    {
        return $request->validate([
            'name' => 'required|string|max:200', 'tin' => ['required', 'string', 'regex:/^[0-9]{9}$/', Rule::unique('hospitals')->ignore($hospital?->id)],
            'region_id' => 'required|integer|exists:regions,id',
            'district_id' => ['required', 'integer', Rule::exists('districts', 'id')->where('region_id', $request->input('region_id'))],
        ]);
    }
}
