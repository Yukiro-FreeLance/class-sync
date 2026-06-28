<?php

namespace App\DTOs\Attendance;

use App\Enums\AttendanceMethod;
use App\Enums\AttendanceStatus;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

readonly class AttendanceRecordDTO
{
    public function __construct(
        public int $studentId,
        public AttendanceStatus $status = AttendanceStatus::Present,
        public AttendanceMethod $method = AttendanceMethod::Manual,
        public ?CarbonInterface $date = null,
        public ?string $timeIn = null,
        public ?string $timeOut = null,
        public ?string $remarks = null,
        public ?int $recordedBy = null,
        public ?float $latitude = null,
        public ?float $longitude = null,
        public ?int $deviceId = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            studentId: (int) $data['student_id'],
            status: isset($data['status'])
                ? (is_string($data['status']) ? AttendanceStatus::from($data['status']) : $data['status'])
                : AttendanceStatus::Present,
            method: isset($data['method'])
                ? (is_string($data['method']) ? AttendanceMethod::from($data['method']) : $data['method'])
                : AttendanceMethod::Manual,
            date: isset($data['date']) ? Carbon::parse($data['date']) : null,
            timeIn: $data['time_in'] ?? null,
            timeOut: $data['time_out'] ?? null,
            remarks: $data['remarks'] ?? null,
            recordedBy: isset($data['user_id']) ? (int) $data['user_id'] : null,
            latitude: isset($data['latitude']) ? (float) $data['latitude'] : null,
            longitude: isset($data['longitude']) ? (float) $data['longitude'] : null,
            deviceId: isset($data['device_id']) ? (int) $data['device_id'] : null,
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'student_id' => $this->studentId,
            'user_id' => $this->recordedBy,
            'date' => ($this->date ?? now())->toDateString(),
            'time_in' => $this->timeIn,
            'time_out' => $this->timeOut,
            'status' => $this->status->value,
            'method' => $this->method->value,
            'remarks' => $this->remarks,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'device_id' => $this->deviceId,
        ], fn ($value) => $value !== null);
    }
}
