<?php
namespace App\Services;

use App\Models\Todo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class TodoService
{
    public function __construct(private LogService $logService) {}

    /**
     * Save or update a todo item.
     *
     * @param array<string, mixed> $data
     * @return \App\Models\Todo
     * @throws \Illuminate\Validation\ValidationException
     */
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
