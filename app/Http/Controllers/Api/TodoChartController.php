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

        $assigneeStrings = $query->select('assignee')->pluck('assignee');

        $summary = [];

        foreach ($assigneeStrings as $row) {
            $names = array_map('trim', explode(',', $row));

            foreach ($names as $name) {
                $cnt = $summary[$name] ?? 0;
                $summary[$name] = $cnt + 1;
            }
        }

        return $summary;
    }
}
