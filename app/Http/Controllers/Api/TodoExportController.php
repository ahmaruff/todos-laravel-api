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

        $filters = [
            'start' => $start,
            'end' => $end,
        ];

        $result = $this->todoService->export($filters);
        $filename = $result['filename'];

        $downloadUrl = route('todos.download', $filename);

        $result['url'] = $downloadUrl;

        return ResponseJsonCommand::responseSuccess('success export', $result);
    }

    public function download(string $filename)
    {
        $path = 'exports/' . basename($filename);

        if (!Storage::disk('local')->exists($path)) {
            return ResponseJsonCommand::responseFail('File not found', [
                'filename' => $filename,
                'exists' => false
            ], Response::HTTP_NOT_FOUND);
        }

        return Storage::disk('local')->download($path);
    }
}
