<?php

namespace App\Http\Controllers\Api;

use App\Commands\ResponseJsonCommand;
use App\Http\Controllers\Controller;
use App\Models\Todo;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TodoChartController extends Controller
{
    public function index(Request $request)
    {
        $filters = [];

        $startDate = $request->query('start_date', null);
        $endDate = $request->query('end_date', null);

        if($startDate) {
            $filters['start_date'] = Carbon::parse($startDate)->utc()->startOfDay();
        }

        if($endDate) {
            $filters['end_date'] = Carbon::parse($endDate)->utc()->endOfDay();
        }

        $type = $request->query('type');
        $type = strtolower(trim($type));
        $data  = [];
        switch ($type) {
            case 'status':
                $data['status_summary'] = $this->statusChart($filters);
                break;
            case 'priority':
                $data['priority_summary'] = $this->priorityChart($filters);
                break;
            case 'assignee':
                $data['assignee_summary'] = $this->assigneeChart($filters);
                break;
            default:
                $data['status_summary'] = $this->statusChart($filters);
                $data['priority_summary'] = $this->priorityChart($filters);
                $data['assignee_summary'] = $this->assigneeChart($filters);
                break;
        }

        return ResponseJsonCommand::responseSuccess("success get chart", $data);
    }

    private function statusChart(array $filters)
    {
        $allStatuses = collect(Todo::$statusList)
            ->mapWithKeys(fn($status) => [$status => 0]);

        $startDate = $filters['start_date'] ?? null;
        $endDate = $filters['end_date'] ?? null;

        $query = Todo::query();

        if($startDate) {
            $query->where('due_date', '>=', $startDate);
        }

        if($endDate) {
            $query->where('due_date', '<=', $endDate);
        }

        $counts = $query->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        $statusSummary = $allStatuses->merge($counts);

        return $statusSummary;
    }

    private function priorityChart(array $filters)
    {
        $allPriorities = collect(Todo::$priorityList)
            ->mapWithKeys(fn($priority) => [$priority => 0]);

        $startDate = $filters['start_date'] ?? null;
        $endDate = $filters['end_date'] ?? null;

        $query = Todo::query();

        if($startDate) {
            $query->where('due_date', '>=', $startDate);
        }

        if($endDate) {
            $query->where('due_date', '<=', $endDate);
        }

        $counts = $query->select('priority', DB::raw('count(*) as total'))
            ->groupBy('priority')
            ->pluck('total', 'priority');

        $prioritySummary = $allPriorities->merge($counts);

        return $prioritySummary;
    }

    private function assigneeChart(array $filters)
    {
        $startDate = $filters['start_date'] ?? null;
        $endDate = $filters['end_date'] ?? null;

        $query = Todo::query();

        if($startDate) {
            $query->where('due_date', '>=', $startDate);
        }

        if($endDate) {
            $query->where('due_date', '<=', $endDate);
        }

        $todos = Todo::select('assignee', 'status', 'time_tracked')->get();

        $summary = [];

        foreach ($todos as $todo) {
            $assignees = array_map('trim', explode(',', $todo->assignee));

            foreach ($assignees as $name) {
                if (!isset($summary[$name])) {
                    $summary[$name] = [
                        'total_todos' => 0,
                        'total_pending_todos' => 0,
                        'total_timetracked_completed_todos' => 0,
                    ];
                }

                $summary[$name]['total_todos']++;

                if ($todo->status === Todo::STATUS_PENDING) {
                    $summary[$name]['total_pending_todos']++;
                }

                if ($todo->status === Todo::STATUS_COMPLETED && $todo->time_tracked > 0) {
                    $summary[$name]['total_timetracked_completed_todos']++;
                }
            }
        }

        return $summary;
    }
}
