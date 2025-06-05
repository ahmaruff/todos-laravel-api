<?php

namespace App\Http\Controllers\Api;

use App\Commands\ResponseJsonCommand;
use App\Http\Controllers\Controller;
use App\Models\Todo;
use App\Services\TodoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class TodoController extends Controller
{
    public function __construct(protected TodoService $todoService){}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $perPage = $request->query('per_page', 10);
        $page = $request->query('page', 1);
        $paginate = $request->query('paginate', 'false');

        $start = $request->query('start', null);
        $end = $request->query('end', null);
        $min = $request->query('min', null);
        $max = $request->query('max', null);
        $title = $request->query('title', null);
        $assignee = $request->query('assignee', null);
        $status = $request->query('status', null);
        $priority = $request->query('priority', null);

        if($paginate == 'true' || $paginate == 1 || $paginate == '1') {
            $paginate = true;
        } else {
            $paginate = false;
        }

        $filters = [
            'start' => $start ?? null,
            'end' => $end ?? null,
            'min' => $min ?? null,
            'max' => $max ?? null,
            'title' => $title ?? null,
            'assignee' => $assignee ?? null,
            'status' => $status ?? null,
            'priority' => $priority ?? null,
            'page' => $page ?? null,
            'per_page' => $perPage ?? null,
        ];

        $result = $this->todoService->list($filters, $paginate);

        if($paginate) {
            return ResponseJsonCommand::responseSuccess('success', $result);
        }

        return ResponseJsonCommand::responseSuccess('success', ['todos' => $result['todos'] ?? []]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $rules = [
            'title' => ['required', 'string'],
            'assignee' => ['sometimes', 'nullable', 'string'],
            'due_date' => ['required', 'date', 'after_or_equal:today'],
            'time_tracked' => ['sometimes','nullable', 'integer'],
            'status' => ['sometimes', 'nullable', 'string',Rule::in(Todo::$statusList)],
            'priority' => ['required', 'string',Rule::in(Todo::$priorityList)],
        ];

        $validator = Validator::make($request->json()->all(), $rules);

        if($validator->fails()){
            throw new ValidationException($validator);
        }

        $validated = $validator->validated();

        $todo = $this->todoService->save($validated);

        return ResponseJsonCommand::responseSuccess("success save todo", ['todo' => $todo], Response::HTTP_CREATED);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $todo = $this->todoService->find($id);
        return ResponseJsonCommand::responseSuccess("success get todo", ['todo' => $todo]);
    }


    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $rules = [
            'title' => ['sometimes','nullable', 'string'],
            'assignee' => ['sometimes', 'nullable', 'string'],
            'due_date' => ['sometimes', 'nullable', 'date'],
            'time_tracked' => ['sometimes','nullable', 'integer'],
            'status' => ['sometimes', 'nullable', 'string',Rule::in(Todo::$statusList)],
            'priority' => ['sometimes', 'nullable', 'string',Rule::in(Todo::$priorityList)],
        ];

        $validator = Validator::make($request->json()->all(), $rules);

        if($validator->fails()){
            throw new ValidationException($validator);
        }

        $validated = $validator->validated();
        $validated['id'] = $id;

        $todo = $this->todoService->save($validated);

        return ResponseJsonCommand::responseSuccess("success save todo", ['todo' => $todo], Response::HTTP_OK);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $result = $this->todoService->delete($id);

        return ResponseJsonCommand::responseSuccess("success delete todo");
    }
}
