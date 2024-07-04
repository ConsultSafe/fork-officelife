<?php

namespace App\Http\Controllers\Company\Dashboard\Me;

use App\Helpers\ImageHelper;
use App\Helpers\InstanceHelper;
use App\Http\Controllers\Controller;
use App\Services\Company\Employee\Answer\CreateAnswer;
use App\Services\Company\Employee\Answer\DestroyAnswer;
use App\Services\Company\Employee\Answer\UpdateAnswer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardQuestionController extends Controller
{
    /**
     * Answer the question.
     */
    public function store(Request $request): JsonResponse
    {
        $company = InstanceHelper::getLoggedCompany();
        $employee = InstanceHelper::getLoggedEmployee();

        $data = [
            'author_id' => $employee->id,
            'employee_id' => $employee->id,
            'company_id' => $company->id,
            'question_id' => $request->input('id'),
            'body' => $request->input('body'),
        ];

        $answer = (new CreateAnswer)->execute($data);

        $allEmployeeAnswers = $answer->question->answers()->with('employee')->take(3)->get();

        $answersCollection = collect([]);
        foreach ($allEmployeeAnswers as $answer) {
            $answersCollection->push([
                'id' => $answer->id,
                'body' => $answer->body,
                'employee' => [
                    'id' => $answer->employee->id,
                    'name' => $answer->employee->name,
                    'avatar' => ImageHelper::getAvatar($answer->employee),
                ],
            ]);
        }

        return response()->json([
            'data' => $answersCollection,
        ], 200);
    }

    /**
     * Update the question.
     */
    public function update(Request $request, int $companyId, int $answerId): JsonResponse
    {
        $loggedEmployee = InstanceHelper::getLoggedEmployee();
        $loggedCompany = InstanceHelper::getLoggedCompany();

        $data = [
            'company_id' => $loggedCompany->id,
            'author_id' => $loggedEmployee->id,
            'employee_id' => $loggedEmployee->id,
            'answer_id' => $answerId,
            'body' => $request->input('body'),
        ];

        $answer = (new UpdateAnswer)->execute($data);

        return response()->json([
            'data' => [
                'id' => $answer->id,
                'body' => $answer->body,
                'employee' => [
                    'id' => $answer->employee->id,
                    'name' => $answer->employee->name,
                    'avatar' => ImageHelper::getAvatar($answer->employee),
                ],
            ],
        ], 200);
    }

    /**
     * Delete the question.
     */
    public function destroy(Request $request, int $companyId, int $answerId): JsonResponse
    {
        $loggedEmployee = InstanceHelper::getLoggedEmployee();
        $loggedCompany = InstanceHelper::getLoggedCompany();

        $data = [
            'company_id' => $loggedCompany->id,
            'author_id' => $loggedEmployee->id,
            'employee_id' => $loggedEmployee->id,
            'answer_id' => $answerId,
        ];

        (new DestroyAnswer)->execute($data);

        return response()->json([
            'data' => true,
        ], 200);
    }
}
