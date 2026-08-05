<?php

namespace App\Http\Controllers;

use App\Models\Form;
use App\Models\FormVersion;
use App\Services\FormVersionManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class FormVersionController extends Controller
{
    public function restore(
        Request $request,
        Form $form,
        FormVersion $version,
        FormVersionManager $versions,
    ): RedirectResponse {
        Gate::authorize('update', $form);
        abort_unless($version->form_id === $form->id, 404);

        $versions->restore($form, $version, $request->user()->id);

        return back()->with('toast', [
            'type' => 'success',
            'message' => "Version {$version->version} restored as a new version.",
        ]);
    }
}
