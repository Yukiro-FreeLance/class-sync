<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\GradeLevel;
use App\Models\Section;
use App\Models\Student;
use App\Models\User;
use App\Services\Students\StudentImportService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class StudentImportExportTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);

        $this->admin = User::factory()->create([
            'is_active' => true,
        ]);
        $this->admin->assignRole('administrator');
    }

    public function test_admin_can_download_import_template(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('students.import.template'));

        $response->assertOk();
        $response->assertDownload('students-import-template.xlsx');
    }

    public function test_admin_can_export_students(): void
    {
        Student::factory()->count(2)->create();

        $response = $this->actingAs($this->admin)
            ->get(route('students.export', ['format' => 'xlsx']));

        $response->assertOk();
        $this->assertStringContainsString('attachment', (string) $response->headers->get('content-disposition'));
    }

    public function test_import_service_creates_students_from_spreadsheet(): void
    {
        $grade = GradeLevel::factory()->create(['name' => 'Grade 10']);
        $section = Section::factory()->create([
            'name' => 'A',
            'grade_level_id' => $grade->id,
        ]);
        $year = AcademicYear::factory()->create([
            'name' => '2025-2026',
            'is_current' => true,
        ]);

        $file = UploadedFile::fake()->createWithContent('students.csv', implode("\n", [
            'student_number,first_name,last_name,middle_name,gender,birth_date,address,grade_level,section,academic_year,status,rfid_tag,medical_notes,enrollment_date',
            ",Ana,Reyes,,female,2010-01-01,Test St,Grade 10,A,{$year->name},active,,,2025-06-01",
        ]));

        $result = app(StudentImportService::class)->import($file);

        $this->assertSame(1, $result->imported);
        $this->assertSame(0, $result->skipped);
        $this->assertDatabaseHas('students', [
            'first_name' => 'Ana',
            'last_name' => 'Reyes',
            'grade_level_id' => $grade->id,
            'section_id' => $section->id,
            'academic_year_id' => $year->id,
        ]);
    }

    public function test_import_service_skips_already_registered_students(): void
    {
        $grade = GradeLevel::factory()->create(['name' => 'Grade 10']);
        Section::factory()->create([
            'name' => 'A',
            'grade_level_id' => $grade->id,
        ]);
        $year = AcademicYear::factory()->create([
            'name' => '2025-2026',
            'is_current' => true,
        ]);

        Student::factory()->create([
            'student_number' => '100777',
            'first_name' => 'Juan',
            'last_name' => 'Dela Cruz',
            'grade_level_id' => $grade->id,
            'academic_year_id' => $year->id,
        ]);

        $file = UploadedFile::fake()->createWithContent('students.csv', implode("\n", [
            'student_number,first_name,last_name,middle_name,gender,birth_date,address,grade_level,section,academic_year,status,rfid_tag,medical_notes,enrollment_date',
            "100777,Pedro,Santos,,male,2010-01-01,Test St,Grade 10,A,{$year->name},active,,,2025-06-01",
            ",Maria,Garcia,,female,2011-02-02,Other St,Grade 10,A,{$year->name},active,,,2025-06-01",
        ]));

        $result = app(StudentImportService::class)->import($file);

        $this->assertSame(1, $result->imported);
        $this->assertSame(1, $result->skipped);
        $this->assertSame([], $result->errors);
        $this->assertCount(1, $result->skippedRows);
        $this->assertStringContainsString('100777', $result->skippedRows[0]['message']);
        $this->assertDatabaseHas('students', ['first_name' => 'Maria', 'last_name' => 'Garcia']);
        $this->assertDatabaseMissing('students', ['first_name' => 'Pedro', 'last_name' => 'Santos']);
    }

    public function test_import_service_updates_existing_when_enabled(): void
    {
        $grade = GradeLevel::factory()->create(['name' => 'Grade 10']);
        Section::factory()->create([
            'name' => 'A',
            'grade_level_id' => $grade->id,
        ]);
        $year = AcademicYear::factory()->create([
            'name' => '2025-2026',
            'is_current' => true,
        ]);

        $existing = Student::factory()->create([
            'student_number' => '100777',
            'first_name' => 'Juan',
            'last_name' => 'Dela Cruz',
            'address' => 'Old address',
            'grade_level_id' => $grade->id,
            'academic_year_id' => $year->id,
        ]);

        $file = UploadedFile::fake()->createWithContent('students.csv', implode("\n", [
            'student_number,first_name,last_name,middle_name,gender,birth_date,address,grade_level,section,academic_year,status,rfid_tag,medical_notes,enrollment_date',
            "100777,Juan,Dela Cruz,,male,2010-01-01,Updated address,Grade 10,A,{$year->name},active,,,2025-06-01",
        ]));

        $result = app(StudentImportService::class)->import($file, updateExisting: true);

        $this->assertSame(1, $result->imported);
        $this->assertSame(0, $result->skipped);
        $this->assertSame([], $result->skippedRows);
        $this->assertDatabaseHas('students', [
            'id' => $existing->id,
            'address' => 'Updated address',
        ]);
    }
}
