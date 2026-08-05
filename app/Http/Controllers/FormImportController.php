<?php

namespace App\Http\Controllers;

use App\Jobs\ImportFormDocument;
use App\Models\Form;
use App\Models\FormAutomation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FormImportController extends Controller
{
    public function store(Request $request, Form $form): RedirectResponse
    {
        Gate::authorize('update', $form);
        $request->validate([
            'document' => ['required', 'file', 'mimes:docx,xlsx', 'max:10240'],
        ]);

        $document = $request->file('document');
        abort_unless($document !== null && $document->isValid(), 422, 'A valid document is required.');

        $extension = Str::lower($document->getClientOriginalExtension());
        $path = $document->storeAs(
            'imports/'.$form->uuid,
            Str::uuid().'.'.$extension,
            ['disk' => 'local'],
        );
        abort_if($path === false, 500, 'The document could not be stored.');

        $automation = FormAutomation::query()->create([
            'uuid' => (string) Str::uuid(),
            'form_id' => $form->id,
            'user_id' => $request->user()->id,
            'type' => 'import',
            'status' => 'queued',
            'input' => [
                'path' => $path,
                'extension' => $extension,
                'original_name' => Str::limit($document->getClientOriginalName(), 255, ''),
                'size' => Storage::disk('local')->size($path),
            ],
        ]);

        ImportFormDocument::dispatch($automation->id);

        return back()->with('toast', [
            'type' => 'success',
            'message' => 'Document queued for import. You can preview it before applying changes.',
        ]);
    }
}
