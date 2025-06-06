<?php

namespace App\Http\Controllers\Api;

use App\Commands\ResponseJsonCommand;
use App\Http\Controllers\Controller;
use App\Services\TodoService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class TodoExportController extends Controller
{
    public function __construct(protected TodoService $todoService){}

    public function excel(Request $request)
    {
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

        $filters = [
            'start' => $start,
            'end' => $end,
        ];

        try {
            $result = $this->todoService->export($filters);
            $filename = $result['filename'];

            $downloadUrl = route('todos.download', $filename);
            $result['url'] = $downloadUrl;
            return ResponseJsonCommand::responseSuccess('success export', $result);
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function download(string $filename)
    {
        try {
            $path = 'exports/' . basename($filename);

            if (!Storage::disk('local')->exists($path)) {
                return ResponseJsonCommand::responseFail('File not found', [
                    'filename' => $filename,
                    'exists' => false
                ], Response::HTTP_NOT_FOUND);
            }

            return Storage::disk('local')->download($path);
        } catch (\Throwable $th) {
            throw $th;
        }
    }
}
