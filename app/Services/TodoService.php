<?php
namespace App\Services;

use App\Commands\ModelPaginationCommand;
use App\Models\Todo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class TodoService
{
    public function __construct(private LogService $logService) {}

    public function list(array $filters = [], bool $paginate = false)
    {
        $query = Todo::query();

        $perPage = $filters['per_page'] ?? 10;

        if (!empty($filters['title'])) {
            $query->where('title', 'like', '%' . $filters['title'] . '%');
        }

        if (!empty($filters['start'])) {
            $query->whereDate('due_date', '>=', $filters['start']);
        }

        if (!empty($filters['end'])) {
            $query->whereDate('due_date', '<=', $filters['end']);
        }

        if (!empty($filters['min'])) {
            $query->whereDate('time_tracked', '>=', $filters['min']);
        }

        if (!empty($filters['max'])) {
            $query->whereDate('time_tracked', '<=', $filters['max']);
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
            $paginator = $query->latest()->paginate($perPage);
            return [
                'todos' => $paginator->items(),
                'pagination' => ModelPaginationCommand::execute($paginator, $filters, $baseUrl = null),
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

        $rules = [
            'id' => ['sometimes', 'nullable', 'exists:todos,id'],
            'title' => ['required', 'string'],
            'assignee' => ['sometimes', 'nullable', 'string'],
            'due_date' => ['required', 'date', 'after_or_equal:today'],
            'time_tracked' => ['sometimes','nullable', 'integer'],
            'status' => ['sometimes', 'nullable', 'string',Rule::in(Todo::$statusList)],
            'priority' => ['required', 'string',Rule::in(Todo::$priorityList)],
        ];

        $validator = Validator::make($data, $rules);

        if($validator->fails()){
            throw new ValidationException($validator);
        }

        $validated = $validator->validated();
        $validated = $this->handleDefaultValue($validated);

        DB::beginTransaction();

        try {
            $isUpdate = isset($validated['id']);

            if ($isUpdate) {
                $todo = Todo::findOrFail($validated['id']);
                $todo->update($validated);
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
}
