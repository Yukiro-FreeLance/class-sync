<?php

namespace App\Repositories;

use App\Models\AttendanceRecord;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;

class AttendanceRepository
{
    public function __construct(
        protected AttendanceRecord $model,
    ) {}

    public function create(array $data): AttendanceRecord
    {
        return $this->model->newQuery()->create($data);
    }

    public function find(int $id): ?AttendanceRecord
    {
        return $this->model->newQuery()->with('student')->find($id);
    }

    public function findForStudentOnDate(int $studentId, CarbonInterface $date): ?AttendanceRecord
    {
        return $this->model->newQuery()
            ->where('student_id', $studentId)
            ->whereDate('date', $date)
            ->first();
    }

    /**
     * @return Collection<int, AttendanceRecord>
     */
    public function getForDate(CarbonInterface $date): Collection
    {
        return $this->model->newQuery()
            ->with(['student', 'user'])
            ->whereDate('date', $date)
            ->orderByDesc('time_in')
            ->get();
    }

    /**
     * @return Collection<int, AttendanceRecord>
     */
    public function getForStudentOnDate(int $studentId, CarbonInterface $date): Collection
    {
        return $this->model->newQuery()
            ->where('student_id', $studentId)
            ->whereDate('date', $date)
            ->orderBy('time_in')
            ->get();
    }

    public function getLatestForStudent(int $studentId): ?AttendanceRecord
    {
        return $this->model->newQuery()
            ->where('student_id', $studentId)
            ->orderByDesc('date')
            ->orderByDesc('time_in')
            ->first();
    }

    /**
     * @return SupportCollection<string, int>
     */
    public function getDailyStats(CarbonInterface $date): SupportCollection
    {
        return $this->model->newQuery()
            ->selectRaw('status, COUNT(*) as total')
            ->whereDate('date', $date)
            ->groupBy('status')
            ->pluck('total', 'status');
    }

    /**
     * @return Collection<int, AttendanceRecord>
     */
    public function getBetweenDates(CarbonInterface $start, CarbonInterface $end): Collection
    {
        return $this->model->newQuery()
            ->with('student')
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->orderBy('date')
            ->orderBy('time_in')
            ->get();
    }

    /**
     * @return Collection<int, AttendanceRecord>
     */
    public function getStudentsInsideCampus(CarbonInterface $date): Collection
    {
        return $this->model->newQuery()
            ->with('student')
            ->whereDate('date', $date)
            ->whereNotNull('time_in')
            ->whereNull('time_out')
            ->orderByDesc('time_in')
            ->get();
    }

    public function update(AttendanceRecord $record, array $data): AttendanceRecord
    {
        $record->update($data);

        return $record->fresh();
    }
}
