<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreStudentRequest;
use App\Http\Requests\Api\UpdateStudentRequest;
use App\Models\Student;
use App\Services\Students\StudentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StudentController extends Controller
{
    public function __construct(
        protected StudentService $studentService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Student::class);

        $students = Student::query()
            ->with(['gradeLevel', 'section'])
            ->when($request->search, fn ($q, $search) => $q->where(function ($query) use ($search) {
                $query->where('student_number', 'like', "%{$search}%")
                    ->orWhere('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%");
            }))
            ->when($request->grade_level_id, fn ($q, $id) => $q->where('grade_level_id', $id))
            ->when($request->section_id, fn ($q, $id) => $q->where('section_id', $id))
            ->paginate($request->integer('per_page', 15));

        return response()->json($students);
    }

    public function show(Student $student): JsonResponse
    {
        $this->authorize('view', $student);

        return response()->json($student->load([
            'gradeLevel', 'section', 'course', 'guardians', 'emergencyContacts',
        ]));
    }

    public function store(StoreStudentRequest $request): JsonResponse
    {
        $this->authorize('create', Student::class);

        $student = $this->studentService->create($request->validated());

        return response()->json($student, 201);
    }

    public function update(UpdateStudentRequest $request, Student $student): JsonResponse
    {
        $this->authorize('update', $student);

        $student = $this->studentService->update($student, $request->validated());

        return response()->json($student);
    }
}
