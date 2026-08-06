<?php

use App\Http\Controllers\AiFormController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FormAutomationController;
use App\Http\Controllers\FormController;
use App\Http\Controllers\FormImportController;
use App\Http\Controllers\FormSubmissionController;
use App\Http\Controllers\FormVersionController;
use App\Http\Controllers\PublicFormController;
use App\Http\Controllers\WelcomeController;
use Illuminate\Support\Facades\Route;

Route::get('/', WelcomeController::class)->name('home');

Route::get('f/{slug}', [PublicFormController::class, 'show'])->name('public.forms.show');
Route::post('f/{slug}', [PublicFormController::class, 'submit'])
    ->middleware('throttle:form-submissions')
    ->name('public.forms.submit');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');
    Route::resource('forms', FormController::class)->only(['index', 'store', 'edit', 'update', 'destroy']);
    Route::post('forms/{form}/preview', [FormController::class, 'preparePreview'])->name('forms.preview.prepare');
    Route::get('forms/{form}/preview', [FormController::class, 'preview'])->name('forms.preview');
    Route::patch('forms/{form}/publish', [FormController::class, 'publish'])->name('forms.publish');
    Route::patch('forms/{form}/slug', [FormController::class, 'updateSlug'])->name('forms.slug.update');
    Route::post('forms/{form}/ai', [AiFormController::class, 'store'])
        ->middleware('throttle:10,1')
        ->name('forms.ai.store');
    Route::post('forms/{form}/imports', [FormImportController::class, 'store'])
        ->middleware('throttle:10,1')
        ->name('forms.imports.store');
    Route::get('forms/{form}/automations/latest', [FormAutomationController::class, 'latest'])
        ->name('forms.automations.latest');
    Route::post('form-automations/{automation}/apply', [FormAutomationController::class, 'apply'])
        ->name('form-automations.apply');
    Route::post('forms/{form}/versions/{version}/restore', [FormVersionController::class, 'restore'])
        ->name('forms.versions.restore');
    Route::get('forms/{form}/submissions', [FormSubmissionController::class, 'index'])->name('forms.submissions.index');
    Route::get('forms/{form}/submissions/export', [FormSubmissionController::class, 'export'])->name('forms.submissions.export');
});

require __DIR__.'/settings.php';
