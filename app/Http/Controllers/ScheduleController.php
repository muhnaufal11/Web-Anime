<?php

namespace App\Http\Controllers;

use App\Models\Schedule;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ScheduleController extends Controller
{
    /**
     * Display the schedule page.
     */
    public function index()
    {
        $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
        
        // Get schedules grouped by day
        $schedulesByDay = [];
        foreach ($days as $day) {
            $schedules = Schedule::with(['anime.genres'])
                ->active()
                ->byDay($day)
                ->orderBy('broadcast_time')
                ->get();
            
            $schedulesByDay[$day] = $schedules;
        }
        
        // Get current day
        $currentDay = Carbon::now()->format('l'); // Monday, Tuesday, etc.
        
        return view('schedule', [
            'schedulesByDay' => $schedulesByDay,
            'currentDay' => $currentDay,
            'days' => $days,
        ]);
    }
}
