<?php
namespace App\Services;

use App\Commands\ModelPaginationCommand;
use App\Models\Todo;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;
use Symfony\Component\HttpFoundation\Response;

class TodoService
{
    public function __construct(private LogService $logService) {}

    public function list(array $filters = [], bool $paginate = false)
    {
        $page = (int) ($filters['page'] ?? 1);
        $perPage = (int) ($filters['per_page'] ?? 10);

        unset($filters['page'], $filters['per_page']);

        $start = null;
        $end = null;

        if (!empty($filters['start'])) {
            $start = Carbon::parse($filters['start'])->startOfDay()->utc();
        }

        if (!empty($filters['end'])) {
            $end = Carbon::parse($filters['end'])->endOfDay()->utc();
        }

        // === start query ===
        $query = Todo::query();

        if (!empty($filters['title'])) {
            $query->where('title', 'like', '%' . $filters['title'] . '%');
        }

        if ($start && $end) {
            $query->whereBetween('due_date', [$start, $end]);
        } else {
            if ($start) {
                $query->where('due_date', '>=', $start);
            }
            if ($end) {
                $query->where('due_date', '<=', $end);
            }
        }

        if (!empty($filters['min'])) {
            $query->where('time_tracked', '>=', $filters['min']);
        }

        if (!empty($filters['max'])) {
            $query->where('time_tracked', '<=', $filters['max']);
        }

        if (!empty($filters['assignee'])) {
            $assignees = array_map('trim', explode(',', $filters['assignee']));
            $query->where(function ($q) use ($assignees) {
                foreach ($assignees as $assignee) {
                    $q->orWhere(function ($q2) use ($assignee) {
                        $q2->where('assignee', $assignee)
                        ->orWhere('assignee', 'LIKE', "$assignee,%")
                        ->orWhere('assignee', 'LIKE', "%, $assignee")
                        ->orWhere('assignee', 'LIKE', "%,$assignee")
                        ->orWhere('assignee', 'LIKE', "%, $assignee,%")
                        ->orWhere('assignee', 'LIKE', "%,$assignee,%");
                    });
                }
            });
        }

        if (!empty($filters['status'])) {
            $status = trim($filters['status']);
            $query->where('status', $status);
        }

        // Filter by priority
        if (!empty($filters['priority'])) {
            $priority = trim($filters['priority']);
            $query->where('priority', $priority);
        }


        if($paginate) {
            $result = ModelPaginationCommand::execute($query, $perPage, $page, $filters,  null);

            return [
                'todos' => $result['items'],
                'pagination' => $result['pagination']
            ];
        }

        $todos = $query->latest()->get();
        return [
            'todos' => $todos,
            'pagination' => null
        ];
    }

    public function save($data)
    {
        $this->logService->start()->task();

        $isUpdate = isset($data['id']);

        if($isUpdate) {
            $dueDateRules = ['sometimes'];
        } else {
            $dueDateRules = ['required', 'date', 'after_or_equal:today'];
        }

        $rules = [
            'id' => ['sometimes', 'nullable', 'exists:todos,id'],
            'title' => [$isUpdate ? 'sometimes' : 'required', 'string'],
            'assignee' => ['sometimes', 'nullable', 'string'],
            'due_date' => $dueDateRules,
            'time_tracked' => ['sometimes', 'nullable', 'integer'],
            'status' => ['sometimes', 'nullable', 'string', Rule::in(Todo::$statusList)],
            'priority' => [$isUpdate ? 'sometimes' : 'required', 'string', Rule::in(Todo::$priorityList)],
        ];

        $validator = Validator::make($data, $rules);

        if($validator->fails()){
            throw new ValidationException($validator);
        }

        $validated = $validator->validated();

        $validated['due_date'] = Carbon::parse($validated['due_date'])->utc()->toDateTimeString();

        $validated = $this->handleDefaultValue($validated);

        DB::beginTransaction();

        try {
            if ($isUpdate) {
                $todo = Todo::findOrFail($validated['id']);
                $todo->fill(Arr::except($validated, 'id'))->save();
            } else {
                $todo = Todo::create($validated);
            }

            DB::commit();

            $this->logService->status(LogService::STATUS_SUCCESS)
                ->code(Response::HTTP_OK)
                ->detectContext(request())
                ->level(LogService::LEVEL_INFO)
                ->message("Successfully saved todo")
                ->response(['todo' => $todo])
                ->save();

            return $todo;

        } catch (\Throwable $e) {
            DB::rollBack();

            throw $e;
        }
    }

    public function find($id): Todo
    {
        $todo = Todo::findOrFail($id);
        return $todo;
    }

    public function delete($id): void
    {
        $this->logService->start()->task();
        DB::beginTransaction();

        try {
            $todo = Todo::findOrFail($id);
            $todo->delete();

            DB::commit();

            $this->logService->status(LogService::STATUS_SUCCESS)
                ->detectContext(request())
                ->message("Deleted todo: $id")
                ->response(['id' => $id])
                ->save();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function export(array $filters)
    {
        // excel data setup
        $disk = Storage::disk('local');

        if(!$disk->exists('exports')) {
            $disk->makeDirectory('exports');
        }

        $fileName = 'todo_export_' . now()->format('Ymd_His') . '.xlsx';
        $filePath = $disk->path("exports/{$fileName}");

        $writer = new Writer();

        $writer->openToFile($filePath);

        $headers = ['title', 'assignee', 'due_date', 'time_tracked', 'status', 'priority'];

        $headerRow = Row::fromValues($headers);
        $writer->addRow($headerRow);

        // start db Query
        $start = null;
        $end = null;

        if (!empty($filters['start'])) {
            $start = Carbon::parse($filters['start'])->startOfDay()->utc();
        }

        if (!empty($filters['end'])) {
            $end = Carbon::parse($filters['end'])->endOfDay()->utc();
        }

        // === start query ===
        $query = Todo::query();

        if (!empty($filters['title'])) {
            $query->where('title', 'like', '%' . $filters['title'] . '%');
        }

        if ($start && $end) {
            $query->whereBetween('due_date', [$start, $end]);
        } else {
            if ($start) {
                $query->where('due_date', '>=', $start);
            }
            if ($end) {
                $query->where('due_date', '<=', $end);
            }
        }

        if (!empty($filters['min'])) {
            $query->where('time_tracked', '>=', $filters['min']);
        }

        if (!empty($filters['max'])) {
            $query->where('time_tracked', '<=', $filters['max']);
        }

        if (!empty($filters['assignee'])) {
            $assignees = array_map('trim', explode(',', $filters['assignee']));
            $query->where(function ($q) use ($assignees) {
                foreach ($assignees as $assignee) {
                    $q->orWhere(function ($q2) use ($assignee) {
                        $q2->where('assignee', $assignee)
                        ->orWhere('assignee', 'LIKE', "$assignee,%")
                        ->orWhere('assignee', 'LIKE', "%, $assignee")
                        ->orWhere('assignee', 'LIKE', "%,$assignee")
                        ->orWhere('assignee', 'LIKE', "%, $assignee,%")
                        ->orWhere('assignee', 'LIKE', "%,$assignee,%");
                    });
                }
            });
        }

        if (!empty($filters['status'])) {
            $status = trim($filters['status']);
            $query->where('status', $status);
        }

        // Filter by priority
        if (!empty($filters['priority'])) {
            $priority = trim($filters['priority']);
            $query->where('priority', $priority);
        }

        $query->select('title', 'assignee', 'due_date', 'time_tracked', 'status', 'priority');

        $totalRow = 0;
        $totalTimeTracked = 0;

        // Stream data in chunks
        $query->chunk(500, function ($todos) use($writer, &$totalRow, &$totalTimeTracked) {
            foreach ($todos as $todo) {
                $todoArr = $todo->toArray();
                if(!empty($todoArr)) {
                    $writer->addRow(Row::fromValues($todoArr));
                    $totalRow += 1;

                    $totalTimeTracked += (int) $todo->time_tracked;
                }
            }
        });

        $summaryRows = [
            Row::fromValues(['total_todos', $totalRow]),
            Row::fromValues(['total_time_tracked', $totalTimeTracked]),
        ];

        $writer->addRows($summaryRows);
        $writer->close();

        // file return
        return [
            'total_row' => $totalRow,
            'total_time_tracked' => $totalTimeTracked,
            'filename' => $fileName
        ];
    }

    public function statusChart(array $filters)
    {
        $allStatuses = collect(Todo::$statusList)
            ->mapWithKeys(fn($status) => [$status => 0]);

        $startDate = $this->convertDate($filters['start_date'] ?? null, 'start');
        $endDate =  $this->convertDate($filters['end_date']  ?? null, 'end');

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

    public function priorityChart(array $filters)
    {
        $allPriorities = collect(Todo::$priorityList)
            ->mapWithKeys(fn($priority) => [$priority => 0]);

        $startDate = $this->convertDate($filters['start_date']  ?? null, 'start');
        $endDate =  $this->convertDate($filters['end_date']  ?? null, 'end');

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

    public function assigneeChart(array $filters)
    {
        $startDate = $this->convertDate($filters['start_date']  ?? null, 'start');
        $endDate =  $this->convertDate($filters['end_date']  ?? null, 'end');

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

    private function handleDefaultValue($data)
    {
        if(!isset($data['time_tracked'])) {
            $data['time_tracked'] = 0;
        }

        if(!isset($data['status'])) {
            $data['status'] = Todo::STATUS_PENDING;
        }

        return $data;
    }

    private function convertDate($date, string $type = 'as_is')
    {
        $type = strtolower($type);
        $typeList = ['start', 'end', 'as_is'];

        if (!in_array($type, $typeList)) {
            return null;
        }

        if (empty($date)) {
            return null;
        }

        try {
            $carbon = Carbon::parse($date)->utc();
            return match ($type) {
                'start' => $carbon->startOfDay(),
                'end' => $carbon->endOfDay(),
                default => $carbon,
            };
        } catch (\Throwable $th) {
            return null;
        }
    }
}
