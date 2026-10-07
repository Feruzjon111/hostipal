<?php

namespace App\Http\Controllers;

use App\Models\Disease;
use App\Models\District;
use App\Models\Region;
use App\Support\Visibility;
use Illuminate\Http\Request;

class LookupController extends Controller
{
    public function __invoke(Request $request)
    {
        return response()->json([
            'regions' => Region::orderBy('name')->get(),
            'districts' => District::orderBy('name')->get(),
            'diseases' => Disease::orderBy('name')->get(),
            'hospitals' => Visibility::hospitals($request->user())->orderBy('name')->get(),
            'doctors' => Visibility::doctors($request->user())->select('id', 'name', 'hospital_id', 'specialization')->orderBy('name')->get(),
        ]);
    }
}
