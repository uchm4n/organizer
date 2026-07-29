<?php

namespace App\Http\Controllers\Download;

use App\Actions\Download\DownloadFileAction;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final class ShowDownloadController extends Controller
{
    public function __invoke(Request $request, string $filename, DownloadFileAction $action): BinaryFileResponse
    {
        $file = $action->handle($filename);

        return response()->download($file->getPathname());
    }
}
