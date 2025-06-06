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

        $start = $request->query('start', null);
        $end = $request->query('end', null);
        $min = $request->query('min', null);
        $max = $request->query('max', null);
        $title = $request->query('title', null);
        $assignee = $request->query('assignee', null);
        $status = $request->query('status', null);
        $priority = $request->query('priority', null);

        $filters = [
            'start' => $start,
            'end' => $end,
            'min' => $min,
            'max' => $max,
            'title' => $title,
            'assignee' => $assignee,
            'status' => $status,
            'priority' => $priority
        ];

        $type = $request->query('type');
        $type = strtolower(trim($type));

        try {
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
        } catch (\Throwable $th) {
            throw $th;
        }
    }
}
