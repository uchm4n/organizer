<?php

namespace App\Http\Controllers\Download;

use App\Data\Api\StatsData;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

final class StatsDownloadController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $query = DB::table('download_stats');

        $filename = $request->query('filename');

        if ($filename !== null) {
            $query->where('filename', $filename);
        }

        $rows = $query->orderByDesc('download_count')->get();

        $data = $rows->map(fn (object $row) => StatsData::fromRow($row));

        return response()->json(['data' => $data]);
    }
}
