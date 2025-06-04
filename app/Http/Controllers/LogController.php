<?php

namespace App\Http\Controllers;

use App\Commands\ResponseJsonCommand;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class LogController extends Controller
{
    public function index(Request $request)
    {
        $date = $request->query('date', now()->format('Y-m-d'));
        $limit = (int) $request->query('limit', 10);
        $sort = $request->query('sort', 'asc');

        $logFile = $this->getLogFile($date);

        if (!File::exists($logFile)) {
            return ResponseJsonCommand::responseFail("Logs not found for date: {$date}");
        }

        // Read last X lines from file
        $lines = $this->readLastLines($logFile, $limit);

        $logs = collect($lines)
            ->map(fn($line) => json_decode(trim($line), true))
            ->filter()
            ->when($sort === 'desc', fn($collection) => $collection->reverse())
            ->values();

        $data = [
            'logs' => $logs
        ];

        return ResponseJsonCommand::responseSuccess("Success get logs for date {$date}", $data);
    }

    private function getLogFile(string $date): string
    {
        return storage_path("logs/activity-{$date}.log");
    }

    private function readLastLines(string $logFile, int $limit): array
    {
        $lines = [];
        $handle = fopen($logFile, 'r');

        if (!$handle) {
            return [];
        }

        try {
            // Go to end of file
            fseek($handle, 0, SEEK_END);
            $fileSize = ftell($handle);

            if ($fileSize === 0) {
                return [];
            }

            $buffer = '';
            $pos = $fileSize;
            $lineCount = 0;

            // Read backwards in chunks
            while ($pos > 0 && $lineCount < $limit) {
                $chunkSize = min(4096, $pos);
                $pos -= $chunkSize;
                fseek($handle, $pos);
                $chunk = fread($handle, $chunkSize);

                $buffer = $chunk . $buffer;

                // Extract complete lines
                while (($newlinePos = strrpos($buffer, "\n")) !== false && $lineCount < $limit) {
                    $line = substr($buffer, $newlinePos + 1);
                    if (!empty(trim($line))) {
                        array_unshift($lines, $line);
                        $lineCount++;
                    }
                    $buffer = substr($buffer, 0, $newlinePos);
                }
            }

            // Handle last line if we read entire file
            if ($pos === 0 && !empty(trim($buffer)) && $lineCount < $limit) {
                array_unshift($lines, $buffer);
            }

        } finally {
            fclose($handle);
        }

        return $lines;
    }
}
