<?php

use App\Models\ManagementUser;
use Illuminate\Support\Facades\Hash;

it('redirects unauthenticated users to management login', function () {
    $this->get('/management')->assertRedirect('/management/login');
});

it('allows management user to login and access dashboard', function () {
    $user = ManagementUser::query()->create([
        'name' => 'Manager',
        'email' => 'manager@example.com',
        'password' => Hash::make('secret123'),
        'role' => 'owner',
        'is_active' => true,
    ]);

    $this->post('/management/login', [
        'email' => $user->email,
        'password' => 'secret123',
    ])->assertRedirect('/management');

    $this->get('/management')->assertSuccessful();
});
