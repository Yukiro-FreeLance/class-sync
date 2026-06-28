<?php

namespace App\Services\Attendance;

use App\DTOs\Attendance\AttendanceRecordDTO;
use App\Enums\AttendanceMethod;
use App\Enums\AttendanceStatus;
use App\Models\AttendanceRecord;
use App\Models\Student;
use App\Repositories\AttendanceRepository;
use App\Repositories\StudentRepository;
use App\Services\Audit\AuditLogService;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection as SupportCollection;
use InvalidArgumentException;

class AttendanceService
{
    public const LATE_CUTOFF = '08:00:00';

    public function __construct(
        protected AttendanceRepository $repository,
        protected StudentRepository $studentRepository,
        protected AuditLogService $auditLog,
    ) {}

    public function record(AttendanceRecordDTO $record): AttendanceRecord
    {
        $date = ($record->date ?? now())->toDateString();
        $existing = $this->repository->findForStudentOnDate($record->studentId, Carbon::parse($date));

        if ($existing) {
            $data = array_filter([
                'time_out' => $record->timeOut ?? now()->format('H:i:s'),
                'status' => $record->status->value,
                'remarks' => $record->remarks,
            ]);

            $attendance = $this->repository->update($existing, $data);
        } else {
            $timeIn = $record->timeIn ?? now()->format('H:i:s');
            $status = $record->status;

            if ($status === AttendanceStatus::Present && $timeIn > self::LATE_CUTOFF) {
                $status = AttendanceStatus::Late;
            }

            $attendance = $this->repository->create([
                'student_id' => $record->studentId,
                'user_id' => $record->recordedBy,
                'date' => $date,
                'time_in' => $timeIn,
                'time_out' => $record->timeOut,
                'status' => $status->value,
                'method' => $record->method->value,
                'remarks' => $record->remarks,
                'latitude' => $record->latitude,
                'longitude' => $record->longitude,
                'device_id' => $record->deviceId,
            ]);
        }

        $this->auditLog->logAttendance($attendance);

        return $attendance->load('student');
    }

    public function recordCheckIn(Student $student, AttendanceMethod $method = AttendanceMethod::Manual, ?int $recordedBy = null): AttendanceRecord
    {
        return $this->record(new AttendanceRecordDTO(
            studentId: $student->id,
            method: $method,
            recordedBy: $recordedBy,
            timeIn: now()->format('H:i:s'),
        ));
    }

    public function recordCheckOut(Student $student, AttendanceMethod $method = AttendanceMethod::Manual, ?int $recordedBy = null): AttendanceRecord
    {
        $existing = $this->repository->findForStudentOnDate($student->id, Carbon::today());

        if (! $existing || $existing->time_out) {
            throw new InvalidArgumentException('Student has no active check-in for today.');
        }

        return $this->record(new AttendanceRecordDTO(
            studentId: $student->id,
            method: $method,
            recordedBy: $recordedBy,
            timeOut: now()->format('H:i:s'),
        ));
    }

    public function recordByIdentifier(string $identifier, AttendanceMethod $method = AttendanceMethod::QrCode, ?int $recordedBy = null): AttendanceRecord
    {
        $student = Student::query()
            ->where('student_number', $identifier)
            ->orWhere('qr_code', $identifier)
            ->orWhere('rfid_tag', $identifier)
            ->first();

        if (! $student) {
            throw new InvalidArgumentException("Student not found: {$identifier}");
        }

        if ($this->isStudentInside($student->id)) {
            return $this->recordCheckOut($student, $method, $recordedBy);
        }

        return $this->recordCheckIn($student, $method, $recordedBy);
    }

    /**
     * @return SupportCollection<string, int>
     */
    public function getDailyStats(?CarbonInterface $date = null): SupportCollection
    {
        return $this->repository->getDailyStats($date ?? Carbon::today());
    }

    /**
     * @return Collection<int, AttendanceRecord>
     */
    public function getStudentsInsideCampus(?CarbonInterface $date = null): Collection
    {
        return $this->repository->getStudentsInsideCampus($date ?? Carbon::today());
    }

    public function isStudentInside(int $studentId, ?CarbonInterface $date = null): bool
    {
        $date ??= Carbon::today();
        $record = $this->repository->findForStudentOnDate($studentId, $date);

        return $record && $record->time_in && ! $record->time_out;
    }

    /**
     * @return Collection<int, AttendanceRecord>
     */
    public function getAttendanceForDate(?CarbonInterface $date = null): Collection
    {
        return $this->repository->getForDate($date ?? Carbon::today());
    }
}
