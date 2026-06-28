<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\AttendanceMethod;
use App\Http\Controllers\Controller;
use App\Services\Attendance\AttendanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class AttendanceController extends Controller
{
    public function __construct(
        protected AttendanceService $attendanceService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $date = $request->date ? Carbon::parse($request->date) : Carbon::today();

        return response()->json(
            $this->attendanceService->getAttendanceForDate($date)
        );
    }

    public function scan(Request $request): JsonResponse
    {
        $data = $request->validate([
            'identifier' => ['required', 'string'],
            'method' => ['nullable', 'string'],
        ]);

        $method = isset($data['method'])
            ? AttendanceMethod::from($data['method'])
            : AttendanceMethod::QrCode;

        $record = $this->attendanceService->recordByIdentifier(
            $data['identifier'],
            $method,
            $request->user()->id,
        );

        return response()->json($record->load('student'));
    }

    public function stats(Request $request): JsonResponse
    {
        $date = $request->date ? Carbon::parse($request->date) : Carbon::today();

        return response()->json([
            'date' => $date->toDateString(),
            'stats' => $this->attendanceService->getDailyStats($date),
        ]);
    }

    public function inside(): JsonResponse
    {
        return response()->json(
            $this->attendanceService->getStudentsInsideCampus()
        );
    }
}
