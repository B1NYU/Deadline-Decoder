<?php

use App\Enums\ProjectStatus;
use App\Models\Project;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected to the login page', function () {
    $this->get(route('projects.index'))->assertRedirect(route('login'));
    $this->post(route('projects.store'), ['title' => 'Tugas'])->assertRedirect(route('login'));

    expect(Project::query()->count())->toBe(0);
});

test('the project list only contains the signed in users projects', function () {
    $user = User::factory()->create();
    Project::factory()->for($user)->count(2)->create();
    Project::factory()->create();

    $this->actingAs($user)
        ->get(route('projects.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->has('projects.data', 2));
});

test('a project is created for the signed in user and ignores injected fields', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();

    $response = $this->actingAs($user)->post(route('projects.store'), [
        'title' => 'Proyek Basis Data',
        'description' => 'Tugas besar semester 5',
        'user_id' => $other->id,
        'status' => ProjectStatus::Ready->value,
    ]);

    $project = Project::query()->firstOrFail();

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('projects.show', $project));

    expect($project->user_id)->toBe($user->id)
        ->and($project->title)->toBe('Proyek Basis Data')
        ->and($project->status)->toBe(ProjectStatus::Draft);
});

test('invalid project input is rejected with a clear message', function (array $payload, string $field, string $message) {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('projects.store'), $payload)
        ->assertSessionHasErrors([$field => $message]);

    expect(Project::query()->count())->toBe(0);
})->with([
    'missing title' => [[], 'title', 'Judul proyek wajib diisi.'],
    'title too long' => [['title' => str_repeat('a', 151)], 'title', 'Judul proyek maksimal 150 karakter.'],
    'description too long' => [['title' => 'Tugas', 'description' => str_repeat('a', 2001)], 'description', 'Deskripsi maksimal 2000 karakter.'],
]);

test('the owner can view a project', function () {
    $project = Project::factory()->create();

    $this->actingAs($project->user)
        ->get(route('projects.show', $project))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('project.id', $project->id)
            ->where('project.title', $project->title));
});

test('another users project cannot be viewed or edited', function (string $routeName) {
    $project = Project::factory()->create();

    $this->actingAs(User::factory()->create())
        ->get(route($routeName, $project))
        ->assertNotFound();
})->with([
    'show' => 'projects.show',
    'edit' => 'projects.edit',
]);

test('the owner can update a project but not its status', function () {
    $project = Project::factory()->create(['description' => 'Lama']);

    $response = $this->actingAs($project->user)
        ->put(route('projects.update', $project), [
            'title' => 'Judul baru',
            'description' => null,
            'status' => ProjectStatus::Ready->value,
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('projects.show', $project));

    $project->refresh();

    expect($project->title)->toBe('Judul baru')
        ->and($project->description)->toBeNull()
        ->and($project->status)->toBe(ProjectStatus::Draft);
});

test('another user cannot update a project', function () {
    $project = Project::factory()->create(['title' => 'Asli']);

    $this->actingAs(User::factory()->create())
        ->put(route('projects.update', $project), ['title' => 'Diubah'])
        ->assertNotFound();

    expect($project->refresh()->title)->toBe('Asli');
});

test('the owner can delete a project', function () {
    $project = Project::factory()->create();

    $this->actingAs($project->user)
        ->delete(route('projects.destroy', $project))
        ->assertRedirect(route('projects.index'));

    expect($project->fresh())->toBeNull();
});

test('another user cannot delete a project', function () {
    $project = Project::factory()->create();

    $this->actingAs(User::factory()->create())
        ->delete(route('projects.destroy', $project))
        ->assertNotFound();

    expect($project->fresh())->not->toBeNull();
});

test('deleting a user also deletes their projects', function () {
    $project = Project::factory()->create();

    $project->user->delete();

    expect(Project::query()->count())->toBe(0);
});
