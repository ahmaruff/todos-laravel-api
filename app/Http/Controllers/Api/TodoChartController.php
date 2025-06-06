<?php

namespace App\Http\Controllers\Api;

use App\Commands\ResponseJsonCommand;
use App\Http\Controllers\Controller;
use App\Services\TodoService;
use Illuminate\Http\Request;

class TodoChartController extends Controller
{
    public function __construct(protected TodoService $todoService){}

    public function index(Request $request)
    {
        $filters = [];

        $startDate = $request->query('start_date', null);
        $endDate = $request->query('end_date', null);

        if($startDate) {
            $filters['start_date'] = $startDate;
        }

        if($endDate) {
            $filters['end_date'] = $endDate;
        }

        $type = $request->query('type');
        $type = strtolower(trim($type));
        $data  = [];
        switch ($type) {
            case 'status':
                $data['status_summary'] = $this->todoService->statusChart($filters);
                break;
            case 'priority':
                $data['priority_summary'] = $this->todoService->priorityChart($filters);
                break;
            case 'assignee':
                $data['assignee_summary'] = $this->todoService->assigneeChart($filters);
                break;
            default:
                $data['status_summary'] = $this->todoService->statusChart($filters);
                $data['priority_summary'] = $this->todoService->priorityChart($filters);
                $data['assignee_summary'] = $this->todoService->assigneeChart($filters);
                break;
        }

        return ResponseJsonCommand::responseSuccess("success get chart", $data);
    }
}
