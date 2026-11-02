<?php

use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

test('the owner is allowed to use their project', function (string $ability) {
    $project = Project::factory()->create();

    expect(Gate::forUser($project->user)->allows($ability, $project))->toBeTrue();
})->with(['view', 'update', 'delete']);

test('other users receive a not found response', function (string $ability) {
    $project = Project::factory()->create();

    $response = Gate::forUser(User::factory()->create())->inspect($ability, $project);

    expect($response->allowed())->toBeFalse()
        ->and($response->status())->toBe(404);
})->with(['view', 'update', 'delete']);
