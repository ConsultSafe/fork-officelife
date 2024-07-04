<?php

namespace App\Http\Controllers\Company\Adminland;

use App\Exceptions\EmailAlreadyUsedException;
use App\Helpers\InstanceHelper;
use App\Helpers\NotificationHelper;
use App\Http\Controllers\Controller;
use App\Http\ViewHelpers\Adminland\AdminEmployeeViewHelper;
use App\Models\Company\Employee;
use App\Services\Company\Adminland\Employee\AddEmployeeToCompany;
use App\Services\Company\Adminland\Employee\DestroyEmployee;
use App\Services\Company\Adminland\Employee\InviteEmployeeToBecomeUser;
use App\Services\Company\Adminland\Employee\LockEmployee;
use App\Services\Company\Adminland\Employee\UnlockEmployee;
use App\Services\Company\Employee\HiringDate\SetHiringDate;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Inertia\Inertia;
use Inertia\Response;

class AdminEmployeeController extends Controller
{
    /**
     * Show the list of employees.
     */
    public function index(): Response
    {
        $company = InstanceHelper::getLoggedCompany();
        $employees = $company->employees()->get();

        return Inertia::render('Adminland/Employee/Index', [
            'notifications' => NotificationHelper::getNotifications(InstanceHelper::getLoggedEmployee()),
            'statistics' => AdminEmployeeViewHelper::index($employees, $company),
        ]);
    }

    /**
     * Show the list of all employees.
     */
    public function all(): Response
    {
        $company = InstanceHelper::getLoggedCompany();
        $employees = $company->employees()
            ->orderBy('last_name', 'asc')
            ->get();

        return Inertia::render('Adminland/Employee/IndexAll', [
            'notifications' => NotificationHelper::getNotifications(InstanceHelper::getLoggedEmployee()),
            'employees' => AdminEmployeeViewHelper::all($employees, $company),
        ]);
    }

    /**
     * Show the list of all active employees.
     */
    public function active(): Response
    {
        $company = InstanceHelper::getLoggedCompany();
        $employees = $company->employees()
            ->notLocked()
            ->orderBy('last_name', 'asc')
            ->get();

        return Inertia::render('Adminland/Employee/IndexActive', [
            'notifications' => NotificationHelper::getNotifications(InstanceHelper::getLoggedEmployee()),
            'employees' => AdminEmployeeViewHelper::all($employees, $company),
        ]);
    }

    /**
     * Show the list of all locked employees.
     */
    public function locked(): Response
    {
        $company = InstanceHelper::getLoggedCompany();
        $employees = $company->employees()
            ->where('locked', true)
            ->orderBy('last_name', 'asc')
            ->get();

        return Inertia::render('Adminland/Employee/IndexLocked', [
            'notifications' => NotificationHelper::getNotifications(InstanceHelper::getLoggedEmployee()),
            'employees' => AdminEmployeeViewHelper::all($employees, $company),
        ]);
    }

    /**
     * Show the list of all employees without an hiring date.
     */
    public function noHiringDate(): Response
    {
        $company = InstanceHelper::getLoggedCompany();
        $employees = $company->employees()
            ->where('hired_at', null)
            ->orderBy('created_at', 'desc')
            ->get();

        return Inertia::render('Adminland/Employee/IndexNoHiringDate', [
            'notifications' => NotificationHelper::getNotifications(InstanceHelper::getLoggedEmployee()),
            'employees' => AdminEmployeeViewHelper::all($employees, $company),
        ]);
    }

    /**
     * Show the Create employee view.
     */
    public function create(): Response
    {
        return Inertia::render('Adminland/Employee/Create', [
            'notifications' => NotificationHelper::getNotifications(InstanceHelper::getLoggedEmployee()),
        ]);
    }

    /**
     * Create the employee.
     */
    public function store(Request $request, int $companyId): mixed
    {
        if (config('officelife.demo_mode')) {
            return redirect()->route('home');
        }

        $loggedEmployee = InstanceHelper::getLoggedEmployee();
        $loggedCompany = InstanceHelper::getLoggedCompany();

        $data = [
            'company_id' => $loggedCompany->id,
            'author_id' => $loggedEmployee->id,
            'email' => $request->input('email'),
            'first_name' => $request->input('first_name'),
            'last_name' => $request->input('last_name'),
            'permission_level' => $request->input('permission_level'),
            'send_invitation' => $request->input('send_invitation'),
        ];

        try {
            $employee = (new AddEmployeeToCompany)->execute($data);
        } catch (EmailAlreadyUsedException $e) {
            return response()->json([
                'message' => trans('app.error_email_already_taken'),
            ], 500);
        }

        (new SetHiringDate)->execute([
            'company_id' => $loggedCompany->id,
            'author_id' => $loggedEmployee->id,
            'employee_id' => $employee->id,
            'year' => intval($request->input('year')),
            'month' => intval($request->input('month')),
            'day' => intval($request->input('day')),
        ]);

        return response()->json([
            'company_id' => $companyId,
        ]);
    }

    /**
     * Show the Lock employee view.
     *
     * @return mixed
     */
    public function lock(Request $request, int $companyId, int $employeeId)
    {
        $loggedCompany = InstanceHelper::getLoggedCompany();
        $loggedEmployee = InstanceHelper::getLoggedEmployee();

        if ($loggedCompany->id != $companyId) {
            return redirect('/home');
        }

        if ($employeeId == $loggedEmployee->id) {
            return redirect('/home');
        }

        try {
            $employee = Employee::where('company_id', $loggedCompany->id)
                ->findOrFail($employeeId);
        } catch (ModelNotFoundException $e) {
            return redirect('/home');
        }

        return Inertia::render('Adminland/Employee/Lock/Index', [
            'employee' => [
                'id' => $employee->id,
                'name' => $employee->name,
            ],
            'notifications' => NotificationHelper::getNotifications($loggedEmployee),
        ]);
    }

    /**
     * Lock the employee.
     */
    public function lockAccount(Request $request, int $companyId, int $employeeId): JsonResponse
    {
        $loggedCompany = InstanceHelper::getLoggedCompany();
        $loggedEmployee = InstanceHelper::getLoggedEmployee();

        $data = [
            'company_id' => $loggedCompany->id,
            'employee_id' => $employeeId,
            'author_id' => $loggedEmployee->id,
        ];

        (new LockEmployee)->execute($data);

        return response()->json([
            'company_id' => $companyId,
        ]);
    }

    /**
     * Show the Unlock employee view.
     *
     * @return mixed
     */
    public function unlock(Request $request, int $companyId, int $employeeId)
    {
        $loggedCompany = InstanceHelper::getLoggedCompany();
        $loggedEmployee = InstanceHelper::getLoggedEmployee();

        if ($loggedCompany->id != $companyId) {
            return redirect('/home');
        }

        if ($employeeId == $loggedEmployee->id) {
            return redirect('/home');
        }

        try {
            $employee = Employee::where('company_id', $loggedCompany->id)
                ->findOrFail($employeeId);
        } catch (ModelNotFoundException $e) {
            return redirect('/home');
        }

        return Inertia::render('Adminland/Employee/Unlock/Index', [
            'employee' => [
                'id' => $employee->id,
                'name' => $employee->name,
            ],
            'notifications' => NotificationHelper::getNotifications($loggedEmployee),
        ]);
    }

    /**
     * Unlock the employee.
     */
    public function unlockAccount(Request $request, int $companyId, int $employeeId): JsonResponse
    {
        $loggedCompany = InstanceHelper::getLoggedCompany();
        $loggedEmployee = InstanceHelper::getLoggedEmployee();

        $data = [
            'company_id' => $loggedCompany->id,
            'employee_id' => $employeeId,
            'author_id' => $loggedEmployee->id,
        ];

        (new UnlockEmployee)->execute($data);

        return response()->json([
            'company_id' => $companyId,
        ]);
    }

    /**
     * Show the Delete employee view.
     *
     * @return mixed
     */
    public function delete(Request $request, int $companyId, int $employeeId)
    {
        if (config('officelife.demo_mode')) {
            return redirect()->route('home');
        }

        $loggedCompany = InstanceHelper::getLoggedCompany();
        $loggedEmployee = InstanceHelper::getLoggedEmployee();

        if ($loggedCompany->id != $companyId) {
            return redirect('/home');
        }

        if ($employeeId == $loggedEmployee->id) {
            return redirect('/home');
        }

        try {
            $employee = Employee::where('company_id', $loggedCompany->id)
                ->findOrFail($employeeId);
        } catch (ModelNotFoundException $e) {
            return redirect('/home');
        }

        return Inertia::render('Adminland/Employee/Delete', [
            'employee' => [
                'id' => $employee->id,
                'name' => $employee->name,
            ],
            'notifications' => NotificationHelper::getNotifications($loggedEmployee),
        ]);
    }

    /**
     * Delete the employee.
     */
    public function destroy(Request $request, int $companyId, int $employeeId): JsonResponse
    {
        $loggedCompany = InstanceHelper::getLoggedCompany();
        $loggedEmployee = InstanceHelper::getLoggedEmployee();

        $data = [
            'company_id' => $loggedCompany->id,
            'employee_id' => $employeeId,
            'author_id' => $loggedEmployee->id,
        ];

        (new DestroyEmployee)->execute($data);

        return response()->json([
            'company_id' => $companyId,
        ]);
    }

    /**
     * Show the Invite employee view.
     *
     * @return mixed
     */
    public function invite(Request $request, int $companyId, int $employeeId)
    {
        $loggedCompany = InstanceHelper::getLoggedCompany();
        $loggedEmployee = InstanceHelper::getLoggedEmployee();

        if ($loggedCompany->id != $companyId) {
            return redirect('/home');
        }

        if ($employeeId == $loggedEmployee->id) {
            return redirect('/home');
        }

        try {
            $employee = Employee::where('company_id', $loggedCompany->id)
                ->findOrFail($employeeId);
        } catch (ModelNotFoundException $e) {
            return redirect('/home');
        }

        return Inertia::render('Adminland/Employee/Invite/Index', [
            'employee' => [
                'id' => $employee->id,
                'name' => $employee->name,
                'firstname' => $employee->first_name,
                'email' => $employee->email,
            ],
            'notifications' => NotificationHelper::getNotifications($loggedEmployee),
        ]);
    }

    /**
     * Send an invite to an employee to become user.
     */
    public function sendInvite(Request $request, int $companyId, int $employeeId): JsonResponse
    {
        $loggedCompany = InstanceHelper::getLoggedCompany();
        $loggedEmployee = InstanceHelper::getLoggedEmployee();

        $data = [
            'company_id' => $loggedCompany->id,
            'author_id' => $loggedEmployee->id,
            'employee_id' => $employeeId,
        ];

        (new InviteEmployeeToBecomeUser)->execute($data);

        return response()->json([
            'company_id' => $companyId,
        ]);
    }
}
