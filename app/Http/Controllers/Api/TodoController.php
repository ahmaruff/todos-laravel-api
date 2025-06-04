<?php

namespace App\Http\Controllers\Api;

use App\Commands\ResponseJsonCommand;
use App\Http\Controllers\Controller;
use App\Services\TodoService;
use Illuminate\Http\Request;

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
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
