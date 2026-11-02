<?php

namespace App\Http\Controllers;

use App\Http\Requests\Projects\ProjectRequest;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ProjectController extends Controller
{
    /**
     * Show the signed in user's projects.
     */
    public function index(Request $request): Response
    {
        $projects = $request->user()
            ->projects()
            ->latest()
            ->latest('id')
            ->paginate(12)
            ->through(fn (Project $project): array => $this->projectData($project));

        return Inertia::render('projects/index', [
            'projects' => $projects,
        ]);
    }

    /**
     * Show the form for creating a project.
     */
    public function create(): Response
    {
        return Inertia::render('projects/create');
    }

    /**
     * Store a newly created project.
     */
    public function store(ProjectRequest $request): RedirectResponse
    {
        $project = $request->user()->projects()->create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Proyek berhasil dibuat.')]);

        return to_route('projects.show', $project);
    }

    /**
     * Show a project.
     */
    public function show(Project $project): Response
    {
        Gate::authorize('view', $project);

        return Inertia::render('projects/show', [
            'project' => $this->projectData($project),
        ]);
    }

    /**
     * Show the form for editing a project.
     */
    public function edit(Project $project): Response
    {
        Gate::authorize('update', $project);

        return Inertia::render('projects/edit', [
            'project' => $this->projectData($project),
        ]);
    }

    /**
     * Update a project.
     */
    public function update(ProjectRequest $request, Project $project): RedirectResponse
    {
        Gate::authorize('update', $project);

        $project->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Proyek diperbarui.')]);

        return to_route('projects.show', $project);
    }

    /**
     * Delete a project.
     */
    public function destroy(Project $project): RedirectResponse
    {
        Gate::authorize('delete', $project);

        $project->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Proyek dihapus.')]);

        return to_route('projects.index');
    }

    /**
     * @return array{id: int, title: string, description: string|null, status: string, created_at: string|null}
     */
    private function projectData(Project $project): array
    {
        return [
            'id' => $project->id,
            'title' => $project->title,
            'description' => $project->description,
            'status' => $project->status->value,
            'created_at' => $project->created_at?->toIso8601String(),
        ];
    }
}
