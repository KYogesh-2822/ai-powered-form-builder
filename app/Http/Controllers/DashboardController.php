<?php

namespace App\Http\Controllers;

use App\Models\Form;
use App\Models\FormSubmission;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $formIds = Form::query()->whereBelongsTo($request->user())->select('id');

        return Inertia::render('dashboard', [
            'stats' => [
                'forms' => (clone $formIds)->count(),
                'published' => Form::query()->whereBelongsTo($request->user())->where('status', 'published')->count(),
                'submissions' => FormSubmission::query()->whereIn('form_id', $formIds)->count(),
                'submissionsThisMonth' => FormSubmission::query()
                    ->whereIn('form_id', $formIds)
                    ->where('submitted_at', '>=', now()->startOfMonth())
                    ->count(),
            ],
            'recentForms' => Form::query()
                ->whereBelongsTo($request->user())
                ->withCount('submissions')
                ->latest('updated_at')
                ->limit(5)
                ->get(['id', 'uuid', 'title', 'slug', 'status', 'updated_at']),
            'recentSubmissions' => FormSubmission::query()
                ->whereIn('form_id', $formIds)
                ->with('form:id,uuid,title')
                ->latest('submitted_at')
                ->limit(5)
                ->get(['id', 'form_id', 'reference', 'submitted_at']),
        ]);
    }
}
