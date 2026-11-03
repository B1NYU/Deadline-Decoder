<?php

use App\Models\Project;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('the dashboard summarises only the signed in users projects', function () {
    $user = User::factory()->create();
    Project::factory()->for($user)->count(2)->create();
    Project::factory()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('dashboard')
            ->where('projectCount', 2)
            ->has('recentProjects', 2));
});

test('the dashboard lists at most five recent projects', function () {
    $user = User::factory()->create();
    Project::factory()->for($user)->count(7)->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('projectCount', 7)
            ->has('recentProjects', 5));
});
