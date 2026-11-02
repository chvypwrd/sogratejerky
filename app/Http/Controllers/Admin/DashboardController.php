<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AdminDashboardService;
use App\Services\AnalyticsService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Throwable;

class DashboardController extends Controller
{
    public function __invoke(Request $request, AdminDashboardService $dashboard, AnalyticsService $analytics)
    {
        $month = $request->query('month', now()->format('Y-m'));
        $month = is_string($month) ? $month : now()->format('Y-m');

        try {
            $monthDate = Carbon::createFromFormat('!Y-m', $month);
        } catch (Throwable) {
            $monthDate = now();
        }

        if (! $monthDate || $monthDate->year < 2000 || $monthDate->year > 2100) {
            $monthDate = now();
        }

        return view('admin.dashboard.index', [
            'report' => $dashboard->report($monthDate),
            'ga4Configured' => $analytics->enabled(),
        ]);
    }
}
