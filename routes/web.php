<?php

use App\Http\Controllers\Reports\ReportExportController;
use App\Http\Controllers\Settings\ApplicationPackageController;
use App\Http\Controllers\Students\StudentListExportController;
use App\Livewire\Attendance\Bulk as AttendanceBulk;
use App\Livewire\Attendance\Index as AttendanceIndex;
use App\Livewire\Attendance\LiveMonitor;
use App\Livewire\Attendance\Scanner;
use App\Livewire\AuditLogs\Index as AuditLogsIndex;
use App\Livewire\Dashboard\Index as DashboardIndex;
use App\Livewire\Help\Index as HelpIndex;
use App\Livewire\Reports\Index as ReportsIndex;
use App\Livewire\Settings\Academic\Rooms as AcademicRooms;
use App\Livewire\Settings\Academic\Schedules as AcademicSchedules;
use App\Livewire\Settings\Academic\Sections as AcademicSections;
use App\Livewire\Settings\Academic\Structure as AcademicStructure;
use App\Livewire\Settings\Academic\Subjects as AcademicSubjects;
use App\Livewire\Settings\Academic\Years as AcademicYears;
use App\Livewire\Settings\ApplicationPackage;
use App\Livewire\Settings\AttendanceConfig;
use App\Livewire\Settings\Backup;
use App\Livewire\Settings\General;
use App\Livewire\Settings\Users\Create as SettingsUsersCreate;
use App\Livewire\Settings\Users\Edit as SettingsUsersEdit;
use App\Livewire\Settings\Users\Index as SettingsUsersIndex;
use App\Livewire\Settings\Users\Roles as SettingsUsersRoles;
use App\Livewire\Students\BulkEnroll as StudentsBulkEnroll;
use App\Livewire\Students\ClassList as StudentsClassList;
use App\Livewire\Students\Create as StudentsCreate;
use App\Livewire\Students\Edit as StudentsEdit;
use App\Livewire\Students\Enroll as StudentsEnroll;
use App\Livewire\Students\Index as StudentsIndex;
use App\Livewire\Students\MasterList as StudentsMasterList;
use App\Livewire\Students\QrGenerator;
use App\Livewire\Students\Show as StudentsShow;
use App\Livewire\Teachers\Create as TeachersCreate;
use App\Livewire\Teachers\Index as TeachersIndex;
use App\Livewire\Teachers\Show as TeachersShow;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome');

Route::middleware(['installed', 'auth'])->group(function () {
    Route::get('/dashboard', DashboardIndex::class)->name('dashboard');

    Route::prefix('students')->name('students.')->group(function () {
        Route::get('/', StudentsIndex::class)->name('index');
        Route::get('/enrollment', StudentsBulkEnroll::class)->name('enrollment');
        Route::get('/lists/master', StudentsMasterList::class)->name('lists.master');
        Route::get('/lists/class', StudentsClassList::class)->name('lists.class');
        Route::get('/lists/master/export', [StudentListExportController::class, 'masterList'])->name('lists.master.export');
        Route::get('/lists/class/export', [StudentListExportController::class, 'classList'])->name('lists.class.export');
        Route::get('/create', StudentsCreate::class)->name('create');
        Route::get('/qr', QrGenerator::class)->name('qr');
        Route::get('/import/template', [StudentImportExportController::class, 'template'])->name('import.template');
        Route::get('/export', [StudentImportExportController::class, 'export'])->name('export');
        Route::get('/{student}/enroll', StudentsEnroll::class)->name('enroll');
        Route::get('/{student}/edit', StudentsEdit::class)->name('edit');
        Route::get('/{student}', StudentsShow::class)->name('show');
    });

    Route::prefix('teachers')->name('teachers.')->group(function () {
        Route::get('/', TeachersIndex::class)->name('index');
        Route::get('/create', TeachersCreate::class)->name('create');
        Route::get('/{teacher}', TeachersShow::class)->name('show');
    });

    Route::prefix('attendance')->name('attendance.')->group(function () {
        Route::get('/', AttendanceIndex::class)->name('index');
        Route::get('/bulk', AttendanceBulk::class)->name('bulk');
        Route::get('/scanner', Scanner::class)->name('scanner');
        Route::get('/monitor', LiveMonitor::class)->name('monitor');
    });

    Route::get('/reports', ReportsIndex::class)->name('reports.index');
    Route::get('/reports/export', ReportExportController::class)->name('reports.export');

    Route::prefix('settings')->name('settings.')->group(function () {
        Route::get('/', General::class)->name('general');
        Route::redirect('/academic', '/settings/academic/structure')->name('academic');
        Route::prefix('academic')->name('academic.')->group(function () {
            Route::get('/structure', AcademicStructure::class)->name('structure');
            Route::get('/years', AcademicYears::class)->name('years');
            Route::get('/sections', AcademicSections::class)->name('sections');
            Route::get('/rooms', AcademicRooms::class)->name('rooms');
            Route::get('/subjects', AcademicSubjects::class)->name('subjects');
            Route::get('/schedules', AcademicSchedules::class)->name('schedules');
        });
        Route::get('/backup', Backup::class)->name('backup');
        Route::get('/application-package', ApplicationPackage::class)->name('application-package');
        Route::get('/application-package/download/{filename}', [ApplicationPackageController::class, 'download'])
            ->name('application-package.download');
        Route::get('/application-package/icon', [ApplicationPackageController::class, 'icon'])
            ->name('application-package.icon');
        Route::get('/attendance', AttendanceConfig::class)->name('attendance');
        Route::prefix('users')->name('users.')->group(function () {
            Route::get('/', SettingsUsersIndex::class)->name('index');
            Route::get('/roles', SettingsUsersRoles::class)->name('roles');
            Route::get('/create', SettingsUsersCreate::class)->name('create');
            Route::get('/{user}/edit', SettingsUsersEdit::class)->name('edit');
        });
    });

    Route::get('/audit-logs', AuditLogsIndex::class)->name('audit-logs.index');
    Route::get('/help', HelpIndex::class)->name('help.index');
});

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

require __DIR__.'/auth.php';
require __DIR__.'/setup.php';
