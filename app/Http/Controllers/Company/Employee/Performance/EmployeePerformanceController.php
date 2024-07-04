<?php

namespace App\Http\Controllers\Company\Employee\Performance;

use App\Helpers\InstanceHelper;
use App\Helpers\NotificationHelper;
use App\Helpers\PermissionHelper;
use App\Http\Controllers\Controller;
use App\Http\ViewHelpers\Employee\EmployeePerformanceViewHelper;
use App\Http\ViewHelpers\Employee\EmployeeShowViewHelper;
use App\Models\Company\Employee;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EmployeePerformanceController extends Controller
{
    /**
     * Display the performance of an employee.
     *
     * @return \Illuminate\Http\RedirectResponse|Response
     */
    public function show(Request $request, int $companyId, int $employeeId)
    {
        $company = InstanceHelper::getLoggedCompany();
        $loggedEmployee = InstanceHelper::getLoggedEmployee();

        try {
            $employee = Employee::where('company_id', $company->id)
                ->where('id', $employeeId)
                ->with('company')
                ->with('user')
                ->with('status')
                ->firstOrFail();
        } catch (ModelNotFoundException $e) {
            return redirect('home');
        }

        // information about the logged employee
        $permissions = PermissionHelper::permissions($loggedEmployee, $employee);

        // the latest one on ones
        $oneOnOnes = EmployeeShowViewHelper::oneOnOnes($employee, $permissions, $loggedEmployee);

        // surveys
        $surveys = EmployeePerformanceViewHelper::latestRateYourManagerSurveys($employee);

        // information about the employee, that depends on what the logged Employee can see
        $employee = EmployeeShowViewHelper::informationAboutEmployee($employee, $permissions, $loggedEmployee);

        return Inertia::render('Employee/Performance/Index', [
            'menu' => 'performance',
            'employee' => $employee,
            'permissions' => $permissions,
            'notifications' => NotificationHelper::getNotifications(InstanceHelper::getLoggedEmployee()),
            'surveys' => $surveys,
            'oneOnOnes' => $oneOnOnes,
        ]);
    }
}
