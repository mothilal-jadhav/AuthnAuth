<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;

class ActivityLogController extends Controller
{
    public function index()
    {
        $activity = ActivityLog::with(['causer', 'subject'])
            ->latest()
            ->paginate(25);

        return view('activity.index', compact('activity'));
    }
}
