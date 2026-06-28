<?php

namespace App\Services\Help;

class UserManualService
{
    /**
     * @return list<array{id: string, title: string, icon: string, summary: string, sections: list<array{title: string, steps: list<string>, tips?: list<string>}>}>
     */
    public function manualSections(): array
    {
        return [
            [
                'id' => 'getting-started',
                'title' => 'Getting Started',
                'icon' => 'M13 10V3L4 14h7v7l9-11h-7z',
                'summary' => 'First login, navigation, and quick tips for new users.',
                'sections' => [
                    [
                        'title' => 'Logging In',
                        'steps' => [
                            'Open Class Sync in your browser and sign in with the username and password provided by your administrator.',
                            'After a successful login you are taken to the Dashboard.',
                            'Use the profile menu (top-right) to update your account or log out.',
                        ],
                    ],
                    [
                        'title' => 'Navigating the App',
                        'steps' => [
                            'Use the sidebar on the left to move between modules. Menu items depend on your role and permissions.',
                            'Press Ctrl + K (or click the search bar) to open the command palette and jump to any page quickly.',
                            'Collapse the sidebar with the arrow button at the bottom on larger screens.',
                            'Toggle dark mode with the sun/moon icon in the top bar.',
                        ],
                    ],
                    [
                        'title' => 'Roles & Permissions',
                        'steps' => [
                            'Class Sync supports multiple roles: Administrator, Registrar, Teacher, Guidance, Accounting, Cashier, Principal, Clinic, and Security.',
                            'If you cannot see a menu item or action button, your account may not have permission. Contact your administrator.',
                        ],
                    ],
                ],
            ],
            [
                'id' => 'dashboard',
                'title' => 'Dashboard',
                'icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6',
                'summary' => 'Overview of attendance stats, trends, and quick actions.',
                'sections' => [
                    [
                        'title' => 'Reading the Dashboard',
                        'steps' => [
                            'View today\'s attendance summary: present, late, absent, and excused counts.',
                            'Charts show trends over recent days to spot patterns.',
                            'Quick links may appear for common tasks based on your role.',
                        ],
                    ],
                ],
            ],
            [
                'id' => 'students',
                'title' => 'Students',
                'icon' => 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z',
                'summary' => 'Student records, enrollment, import/export, and class lists.',
                'sections' => [
                    [
                        'title' => 'Browse & Search Students',
                        'steps' => [
                            'Go to Students in the sidebar.',
                            'Search by name, student ID, or RFID tag.',
                            'Filter by department, grade, section, or enrollment status.',
                            'Click a row to open the student profile.',
                        ],
                    ],
                    [
                        'title' => 'Add a New Student',
                        'steps' => [
                            'Click Add Student on the Students page.',
                            'Fill in personal details, contact info, guardians, and optional photo.',
                            'Save to create the record. Enroll the student in a section afterward if needed.',
                        ],
                    ],
                    [
                        'title' => 'Enroll a Student',
                        'steps' => [
                            'Open a student profile and choose Enroll, or use Bulk Enrollment for many students at once.',
                            'Select the academic year, department, grade, section, and semester.',
                            'Confirm enrollment. The student appears in class lists and attendance for that section.',
                        ],
                    ],
                    [
                        'title' => 'Bulk Enrollment',
                        'steps' => [
                            'Open Students → Bulk Enrollment.',
                            'Filter students who need enrollment.',
                            'Select multiple students and assign them to a section in one action.',
                        ],
                    ],
                    [
                        'title' => 'Archive or Delete a Student',
                        'steps' => [
                            'Open a student profile (administrators only by default).',
                            'Use Archive to hide the student from lists and attendance while keeping their records for audit.',
                            'Use Restore on archived students to bring them back to active records.',
                            'Use Delete permanently only when records must be removed entirely — this cannot be undone.',
                        ],
                        'tips' => [
                            'Enable archive, restore, or delete for other roles under Users & Access → Roles & Restrictions.',
                        ],
                    ],
                    [
                        'title' => 'Import & Export',
                        'steps' => [
                            'Click Import on the Students page to upload a spreadsheet using the provided template.',
                            'Use Export to download student data as Excel (.xlsx) or CSV.',
                            'Download the import template first to ensure columns match the required format.',
                        ],
                        'tips' => [
                            'Fix validation errors shown after import before re-uploading.',
                        ],
                    ],
                    [
                        'title' => 'Master List & Class List',
                        'steps' => [
                            'Master List shows all students with enrollment details across the school.',
                            'Class List shows students grouped by section for advisers and teachers.',
                            'Both pages support filters and export to Excel.',
                        ],
                    ],
                ],
            ],
            [
                'id' => 'teachers',
                'title' => 'Teachers',
                'icon' => 'M4.26 10.147a60.438 60.438 0 0 0-.491 6.347A48.62 48.62 0 0 1 12 20.904a48.62 48.62 0 0 1 8.232-4.41 60.46 60.46 0 0 0-.491-6.347m-15.482 0a50.636 50.636 0 0 0-2.658-.813A59.906 59.906 0 0 1 12 3.493a59.903 59.903 0 0 1 10.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.717 50.717 0 0 1 12 13.489a50.702 50.702 0 0 1 7.74-3.342',
                'summary' => 'Manage teacher accounts and teaching assignments.',
                'sections' => [
                    [
                        'title' => 'View Teachers',
                        'steps' => [
                            'Open Teachers in the sidebar to see all staff with teaching roles.',
                            'Search and filter the list, then click a teacher to view their profile and schedules.',
                        ],
                    ],
                    [
                        'title' => 'Add a Teacher',
                        'steps' => [
                            'Click Add Teacher and complete the account form.',
                            'Assign the Teacher role and enable "Acts as teacher" if they will take attendance.',
                            'Link class schedules under Academic Config so they can mark class attendance.',
                        ],
                    ],
                ],
            ],
            [
                'id' => 'attendance',
                'title' => 'Attendance',
                'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4',
                'summary' => 'Campus gate, class sessions, bulk marking, scanner, and live monitor.',
                'sections' => [
                    [
                        'title' => 'Campus Gate Check-in / Check-out',
                        'steps' => [
                            'Open Attendance and select Campus Gate.',
                            'Search for a student by name or scan/enter their ID or RFID code.',
                            'Record check-in when they arrive and check-out when they leave.',
                        ],
                    ],
                    [
                        'title' => 'Class Session Attendance',
                        'steps' => [
                            'Select Class Session on the Attendance page.',
                            'Choose date, section, and class period (subject schedule).',
                            'Mark each student as Present, Late, Absent, or Excused.',
                            'Save when all students are marked.',
                        ],
                        'tips' => [
                            'Teachers only see sections and schedules assigned to them.',
                        ],
                    ],
                    [
                        'title' => 'Bulk Class Attendance',
                        'steps' => [
                            'Open Bulk Class Attendance from Attendance or the command palette.',
                            'Select section and period, then mark all students at once.',
                            'Useful for advisers marking an entire class quickly.',
                        ],
                    ],
                    // [
                    //     'title' => 'QR / RFID Scanner',
                    //     'steps' => [
                    //         'Open Scanner from the command palette (Ctrl + K → Scanner).',
                    //         'Enter a student QR code or RFID tag manually, or use a connected scanner device.',
                    //         'Each scan records attendance for the current context.',
                    //     ],
                    // ],
                    // [
                    //     'title' => 'Live Monitor',
                    //     'steps' => [
                    //         'Open Live Monitor to watch real-time check-ins across campus.',
                    //         'View recent activity, counts by status, and period events.',
                    //         'Ideal for security or admin staff at the front desk.',
                    //     ],
                    // ],
                ],
            ],
            [
                'id' => 'reports',
                'title' => 'Reports',
                'icon' => 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z',
                'summary' => 'Generate and export attendance and student reports.',
                'sections' => [
                    [
                        'title' => 'Generate a Report',
                        'steps' => [
                            'Go to Reports in the sidebar.',
                            'Choose a report type: daily summary, weekly, monthly, yearly, or student list.',
                            'Set the date range and scope filters (department, grade, section).',
                            'Preview the results on screen, then export as PDF, Excel, or CSV.',
                        ],
                    ],
                ],
            ],
            [
                'id' => 'users',
                'title' => 'Users & Access',
                'icon' => 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z',
                'summary' => 'User accounts, roles, and permission management.',
                'sections' => [
                    [
                        'title' => 'Role Permissions',
                        'steps' => [
                            'Open Users & Access → Roles & Restrictions.',
                            'Select a role and toggle permissions such as archive students, restore archived, or permanently delete.',
                            'Save restrictions. Administrators always have full access.',
                        ],
                    ],
                    [
                        'title' => 'Manage Users',
                        'steps' => [
                            'Open Users & Access in the sidebar.',
                            'Add new staff accounts with name, username, email, and role.',
                            'Edit or deactivate users as needed.',
                        ],
                    ],
                    [
                        'title' => 'Roles & Permissions',
                        'steps' => [
                            'Open the Roles tab to review what each role can do.',
                            'Adjust permission toggles for configurable roles.',
                            'Changes apply immediately to all users with that role.',
                        ],
                        'tips' => [
                            'Only administrators should modify role permissions.',
                        ],
                    ],
                ],
            ],
            [
                'id' => 'academic',
                'title' => 'Academic Config',
                'icon' => 'M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1 0-5H20',
                'summary' => 'Departments, years, sections, rooms, subjects, and schedules.',
                'sections' => [
                    [
                        'title' => 'Academic Structure',
                        'steps' => [
                            'Set up departments (e.g. Junior High, Senior High) with grade levels under each.',
                            'Configure semesters per department if applicable.',
                        ],
                    ],
                    [
                        'title' => 'Academic Years',
                        'steps' => [
                            'Create academic years with start and end dates.',
                            'Mark one year as current — enrollment and reports use this by default.',
                        ],
                    ],
                    [
                        'title' => 'Sections & Rooms',
                        'steps' => [
                            'Create sections under each grade level and assign an adviser.',
                            'Add rooms with capacity for schedule assignment.',
                        ],
                    ],
                    [
                        'title' => 'Subjects & Schedules',
                        'steps' => [
                            'Define subjects/courses offered by the school.',
                            'Build class schedules: pick section, subject, teacher, room, day, and time.',
                            'The system warns about conflicting schedules for teachers or rooms.',
                        ],
                        'tips' => [
                            'Complete schedules before teachers use class attendance.',
                        ],
                    ],
                ],
            ],
            [
                'id' => 'settings',
                'title' => 'Settings',
                'icon' => 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z',
                'summary' => 'School info, branding, themes, and attendance rules.',
                'sections' => [
                    [
                        'title' => 'General Settings',
                        'steps' => [
                            'Update school name, code, address, phone, and email.',
                            'Upload a logo and set the sidebar subtitle.',
                            'Choose default theme (light, dark, or system) and locale.',
                        ],
                    ],
                    [
                        'title' => 'Attendance Configuration',
                        'steps' => [
                            'Open Settings → Attendance (or Configure from the Attendance page).',
                            'Set late threshold minutes, class periods, and remarks.',
                            'Configure how gate vs. class attendance behaves.',
                        ],
                    ],
                ],
            ],
            // [
            //     'id' => 'audit-logs',
            //     'title' => 'Audit Logs',
            //     'icon' => 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z',
            //     'summary' => 'Track who did what and when across the system.',
            //     'sections' => [
            //         [
            //             'title' => 'Review Activity',
            //             'steps' => [
            //                 'Open Audit Logs to see a chronological list of system actions.',
            //                 'Filter by user, action type, or date range.',
            //                 'Each entry shows the user, action, affected record, IP address, and timestamp.',
            //             ],
            //         ],
            //     ],
            // ],
            // [
            //     'id' => 'backups',
            //     'title' => 'Backups',
            //     'icon' => 'M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12',
            //     'summary' => 'Create and restore database backups.',
            //     'sections' => [
            //         [
            //             'title' => 'Create a Backup',
            //             'steps' => [
            //                 'Go to Backups in the sidebar.',
            //                 'Click Create Backup to download a snapshot of the database.',
            //                 'Store backup files in a safe location off the server.',
            //             ],
            //         ],
            //         [
            //             'title' => 'Restore',
            //             'steps' => [
            //                 'Use Restore on an existing backup file when you need to recover data.',
            //                 'Restoring overwrites current data — confirm with your administrator first.',
            //             ],
            //             'tips' => [
            //                 'Schedule regular backups before major data imports or term changes.',
            //             ],
            //         ],
            //     ],
            // ],
        ];
    }

    /**
     * @return list<array{id: string, question: string, answer: string, category: string}>
     */
    public function faqItems(): array
    {
        return [
            [
                'id' => 'faq-login',
                'category' => 'Account',
                'question' => 'I forgot my password. How do I reset it?',
                'answer' => 'Contact your school administrator. They can update your password from Users & Access. Self-service password reset may be available if your school has configured email — check the login page for a "Forgot password" link.',
            ],
            [
                'id' => 'faq-permissions',
                'category' => 'Account',
                'question' => 'Why can\'t I see certain menu items?',
                'answer' => 'Menu visibility is controlled by your role and permissions. For example, only users with student permissions see the Students module. Ask your administrator to review your role assignment.',
            ],
            [
                'id' => 'faq-offline',
                'category' => 'General',
                'question' => 'Does Class Sync work without internet?',
                'answer' => 'Yes. Class Sync is designed for offline and LAN use. Install it on a school server and access it from other computers on the same network — no internet connection is required.',
            ],
            [
                'id' => 'faq-lan',
                'category' => 'General',
                'question' => 'How do other computers connect to Class Sync?',
                'answer' => 'Other devices on your school network open the server address in a browser, for example http://192.168.1.100:8000. The server PC must be running and reachable on the LAN.',
            ],
            [
                'id' => 'faq-import-fail',
                'category' => 'Students',
                'question' => 'My student import failed. What should I do?',
                'answer' => 'Download the import template and match column headers exactly. Common issues: duplicate student IDs, missing required fields, or invalid grade/section names. The import screen lists row-level errors — fix those rows and upload again.',
            ],
            [
                'id' => 'faq-enrollment',
                'category' => 'Students',
                'question' => 'A student exists but does not appear in class attendance.',
                'answer' => 'The student likely is not enrolled in a section for the current academic year. Open their profile and enroll them, or use Bulk Enrollment. Also confirm the academic year is set as current under Academic Config.',
            ],
            [
                'id' => 'faq-teacher-schedule',
                'category' => 'Attendance',
                'question' => 'A teacher sees "No class schedules assigned."',
                'answer' => 'Assign class schedules under Academic Config → Schedules with that teacher as the instructor. Ensure the teacher account has the Teacher role and "Acts as teacher" enabled.',
            ],
            [
                'id' => 'faq-late',
                'category' => 'Attendance',
                'question' => 'How is "Late" determined?',
                'answer' => 'Late status is based on the late threshold configured in Settings → Attendance. Students marked after the allowed minutes from period start are recorded as Late.',
            ],
            [
                'id' => 'faq-scanner',
                'category' => 'Attendance',
                'question' => 'The QR scanner is not working with my camera.',
                'answer' => 'Use the manual code entry field on the Scanner page. USB barcode/RFID scanners typically work as keyboard input — click the field and scan. Camera-based scanning may require HTTPS or localhost in some browsers.',
            ],
            [
                'id' => 'faq-reports-empty',
                'category' => 'Reports',
                'question' => 'My report shows no data.',
                'answer' => 'Check the date range and filters. Ensure attendance was recorded for that period and that students were enrolled in the selected section. Widen filters (e.g. all departments) to test.',
            ],
            [
                'id' => 'faq-backup',
                'category' => 'Backups',
                'question' => 'How often should we back up?',
                'answer' => 'Create backups before term starts, after bulk imports, and at least weekly during active use. Store copies on a separate drive or computer.',
            ],
            [
                'id' => 'faq-dark-mode',
                'category' => 'General',
                'question' => 'How do I switch between light and dark mode?',
                'answer' => 'Click the sun/moon icon in the top-right toolbar. Your preference is saved in the browser. Administrators can set a default theme under General Settings.',
            ],
            [
                'id' => 'faq-command',
                'category' => 'General',
                'question' => 'What is the command palette (Ctrl + K)?',
                'answer' => 'It is a quick-jump search. Press Ctrl + K anywhere in the app to find pages like Students, Attendance, Scanner, or Settings without clicking through the sidebar.',
            ],
            // [
            //     'id' => 'faq-setup',
            //     'category' => 'General',
            //     'question' => 'How do I run first-time installation?',
            //     'answer' => 'Visit /setup on a fresh install. The wizard walks through system checks, database setup, school configuration, and creating the admin account. After installation, /setup is disabled.',
            // ],
        ];
    }

    /**
     * @return list<string>
     */
    public function faqCategories(): array
    {
        return collect($this->faqItems())
            ->pluck('category')
            ->unique()
            ->sort()
            ->values()
            ->all();
    }
}
