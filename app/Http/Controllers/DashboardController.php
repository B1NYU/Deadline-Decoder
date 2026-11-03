<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Show the signed in user's summary.
     */
    public function __invoke(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('dashboard', [
            'projectCount' => $user->projects()->count(),
            'recentProjects' => $user->projects()
                ->latest()
                ->latest('id')
                ->limit(5)
                ->get()
                ->map(fn (Project $project): array => [
                    'id' => $project->id,
                    'title' => $project->title,
                    'status' => $project->status->value,
                    'created_at' => $project->created_at?->toIso8601String(),
                ])
                ->all(),
        ]);
    }
}
