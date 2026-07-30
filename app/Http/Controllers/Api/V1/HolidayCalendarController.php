<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\HolidayCalendar;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HolidayCalendarController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = HolidayCalendar::orderBy('date');

        if ($request->filled('year')) {
            $query->where('year', $request->year);
        }

        return response()->json($query->get());
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'date' => ['required', 'date', 'unique:holiday_calendars,date'],
            'type' => ['required', 'in:national_holiday,joint_leave,special'],
            'description' => ['nullable', 'string', 'max:255'],
            'year' => ['required', 'integer'],
        ]);

        $holiday = HolidayCalendar::create($data);

        return response()->json($holiday, 201);
    }

    public function show(HolidayCalendar $holidayCalendar): JsonResponse
    {
        return response()->json($holidayCalendar);
    }

    public function update(Request $request, HolidayCalendar $holidayCalendar): JsonResponse
    {
        $data = $request->validate([
            'date' => ['sometimes', 'date', 'unique:holiday_calendars,date,'.$holidayCalendar->id],
            'type' => ['sometimes', 'in:national_holiday,joint_leave,special'],
            'description' => ['nullable', 'string', 'max:255'],
            'year' => ['sometimes', 'integer'],
        ]);

        $holidayCalendar->update($data);

        return response()->json($holidayCalendar);
    }

    public function destroy(HolidayCalendar $holidayCalendar): JsonResponse
    {
        $holidayCalendar->delete();

        return response()->json(null, 204);
    }
}
