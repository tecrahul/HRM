<?php

namespace App\Http\Controllers;

class BaseController extends Controller
{
    /**
     * @return array<int, array<string, string>>
     */
    protected function hrModules(): array
    {
        return [
            [
                'title' => 'Employees',
                'description' => 'Centralized records for employee profiles and details.',
                'route' => 'modules.employees.index',
            ],
            [
                'title' => 'Attendance',
                'description' => 'Tracking of check-ins, shifts, and working hours.',
                'route' => 'modules.attendance.index',
            ],
            [
                'title' => 'Payroll',
                'description' => 'Salary processing, benefits, and pay history.',
                'route' => 'modules.payroll.index',
            ],
            [
                'title' => 'Leave',
                'description' => 'Leave requests, approvals, and balance monitoring.',
                'route' => 'modules.leave.index',
            ],
            [
                'title' => 'Holidays',
                'description' => 'Financial-year holiday calendar management.',
                'route' => 'modules.holidays.index',
            ],
            [
                'title' => 'Reports',
                'description' => 'Cross-module analytics across attendance, leave, payroll, and activity.',
                'route' => 'modules.reports.index',
            ],
        ];
    }
}
