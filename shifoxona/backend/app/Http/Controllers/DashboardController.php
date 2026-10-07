<?php

namespace App\Http\Controllers;

use App\Support\Visibility;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        $query = Visibility::patients($request->user());
        $counts = (clone $query)->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');
        $days = [];
        for ($offset = 6; $offset >= 0; $offset--) {
            $date = today()->subDays($offset);
            $days[] = [
                'date' => $date->format('Y-m-d'),
                'count' => (clone $query)->whereDate('registered_at', $date)->count(),
            ];
        }

        return response()->json([
            'total' => (clone $query)->count(),
            'today' => (clone $query)->whereDate('registered_at', today())->count(),
            'waiting' => (int) ($counts['waiting'] ?? 0),
            'in_progress' => (int) ($counts['in_progress'] ?? 0),
            'completed' => (int) ($counts['completed'] ?? 0),
            'doctors' => Visibility::doctors($request->user())->count(),
            'hospitals' => Visibility::hospitals($request->user())->count(),
            'week' => $days,
            'recent' => (clone $query)->with(['doctor:id,name', 'disease', 'hospital:id,name'])->orderByDesc('registered_at')->orderByDesc('id')->limit(6)->get(),
        ]);
    }
}
