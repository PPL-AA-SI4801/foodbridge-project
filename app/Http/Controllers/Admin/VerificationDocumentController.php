<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\VerificationDocument;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class VerificationDocumentController extends Controller
{
    /**
     * Stream a private legality document to the administrator.
     */
    public function show(VerificationDocument $verificationDocument): StreamedResponse
    {
        $disk = Storage::disk('local');

        abort_unless($disk->exists($verificationDocument->file_path), 404);

        $verificationDocument->loadMissing([
            'user.donorProfile',
            'user.recipientProfile',
        ]);

        $extension = pathinfo($verificationDocument->file_path, PATHINFO_EXTENSION);
        $name = Str::slug(
            $verificationDocument->jenisDokumenLabel().' '.
            ($verificationDocument->user->profileName() ?? $verificationDocument->user->nama)
        ) ?: 'dokumen';

        if ($extension !== '') {
            $name .= '.'.$extension;
        }

        return $disk->response(
            $verificationDocument->file_path,
            $name,
            [
                'Cache-Control' => 'no-store, private',
                'X-Content-Type-Options' => 'nosniff',
            ],
            'inline',
        );
    }
}
