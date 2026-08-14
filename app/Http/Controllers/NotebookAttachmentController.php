<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\NoteAttachment;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

final class NotebookAttachmentController extends Controller
{
    use AuthorizesRequests;

    public function show(NoteAttachment $attachment): Response
    {
        $this->authorize('view', $attachment);
        abort_unless(Storage::disk($attachment->disk)->exists($attachment->path), 404);

        return Storage::disk($attachment->disk)->response($attachment->path, $attachment->original_name, ['Content-Type' => $attachment->mime_type]);
    }
}
