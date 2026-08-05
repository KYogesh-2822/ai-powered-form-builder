<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FormController;
use App\Http\Controllers\FormSubmissionController;
use App\Http\Controllers\PublicFormController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::get('f/{slug}', [PublicFormController::class, 'show'])->name('public.forms.show');
Route::post('f/{slug}', [PublicFormController::class, 'submit'])
    ->middleware('throttle:form-submissions')
    ->name('public.forms.submit');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');
    Route::resource('forms', FormController::class)->only(['index', 'store', 'edit', 'update', 'destroy']);
    Route::patch('forms/{form}/publish', [FormController::class, 'publish'])->name('forms.publish');
    Route::patch('forms/{form}/slug', [FormController::class, 'updateSlug'])->name('forms.slug.update');
    Route::get('forms/{form}/submissions', [FormSubmissionController::class, 'index'])->name('forms.submissions.index');
    Route::get('forms/{form}/submissions/export', [FormSubmissionController::class, 'export'])->name('forms.submissions.export');
});

require __DIR__.'/settings.php';
