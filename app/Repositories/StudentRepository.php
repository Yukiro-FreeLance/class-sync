<?php

namespace App\Repositories;

use App\Models\Student;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class StudentRepository
{
    public function __construct(
        protected Student $model,
    ) {}

    public function find(int $id): ?Student
    {
        return $this->model->newQuery()->find($id);
    }

    public function findByStudentNumber(string $studentNumber): ?Student
    {
        return $this->model->newQuery()
            ->where('student_number', $studentNumber)
            ->first();
    }

    public function findByIdentifier(string $identifier): ?Student
    {
        return $this->model->newQuery()
            ->where('student_number', $identifier)
            ->orWhere('qr_code', $identifier)
            ->orWhere('rfid_tag', $identifier)
            ->first();
    }

    public function all(): Collection
    {
        return $this->model->newQuery()
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();
    }

    /**
     * @return LengthAwarePaginator<Student>
     */
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->newQuery()
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->paginate($perPage);
    }

    /**
     * @return Collection<int, Student>
     */
    public function search(string $term, int $limit = 50): Collection
    {
        return $this->model->newQuery()
            ->where(function ($query) use ($term) {
                $query->where('student_number', 'like', "%{$term}%")
                    ->orWhere('first_name', 'like', "%{$term}%")
                    ->orWhere('last_name', 'like', "%{$term}%")
                    ->orWhere('rfid_tag', 'like', "%{$term}%");
            })
            ->orderBy('last_name')
            ->limit($limit)
            ->get();
    }

    public function create(array $data): Student
    {
        return $this->model->newQuery()->create($this->sanitizeAttributes($data));
    }

    public function update(Student $student, array $data): Student
    {
        $student->update($this->sanitizeAttributes($data));

        return $student->fresh();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function sanitizeAttributes(array $data): array
    {
        foreach (['rfid_tag', 'middle_name', 'gender', 'address', 'medical_notes', 'photo'] as $field) {
            if (array_key_exists($field, $data) && blank($data[$field])) {
                $data[$field] = null;
            }
        }

        return $data;
    }

    public function delete(Student $student): bool
    {
        return (bool) $student->delete();
    }

    public function restore(Student $student): bool
    {
        return (bool) $student->restore();
    }

    public function forceDelete(Student $student): bool
    {
        return (bool) $student->forceDelete();
    }

    public function findWithTrashed(int $id): ?Student
    {
        return $this->model->newQuery()->withTrashed()->find($id);
    }
}
