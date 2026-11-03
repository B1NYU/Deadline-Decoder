<?php

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;

test('registration screen can be rendered', function () {
    $this->get(route('register'))->assertOk();
});

test('a new user can register and must verify their email', function () {
    Notification::fake();

    $response = $this->post(route('register'), [
        'name' => 'Mahasiswa Uji',
        'email' => 'mahasiswa@contoh.test',
        'password' => 'kata-sandi-aman-123',
        'password_confirmation' => 'kata-sandi-aman-123',
    ]);

    $user = User::query()->where('email', 'mahasiswa@contoh.test')->firstOrFail();

    $this->assertAuthenticatedAs($user);
    $response->assertRedirect(route('dashboard'));

    expect($user->email_verified_at)->toBeNull()
        ->and(Hash::check('kata-sandi-aman-123', $user->password))->toBeTrue();

    Notification::assertSentTo($user, VerifyEmail::class);
});

test('invalid registration input is rejected', function (array $overrides, string $field) {
    User::factory()->create(['email' => 'terpakai@contoh.test']);

    $payload = array_merge([
        'name' => 'Mahasiswa Uji',
        'email' => 'baru@contoh.test',
        'password' => 'kata-sandi-aman-123',
        'password_confirmation' => 'kata-sandi-aman-123',
    ], $overrides);

    $this->post(route('register'), $payload)->assertSessionHasErrors($field);

    $this->assertGuest();
    expect(User::query()->where('email', 'baru@contoh.test')->exists())->toBeFalse();
})->with([
    'missing name' => [['name' => ''], 'name'],
    'email already taken' => [['email' => 'terpakai@contoh.test'], 'email'],
    'invalid email' => [['email' => 'bukan-email'], 'email'],
    'password confirmation mismatch' => [['password_confirmation' => 'berbeda'], 'password'],
]);
