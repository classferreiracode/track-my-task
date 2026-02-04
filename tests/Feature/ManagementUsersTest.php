<?php

use App\Models\ManagementUser;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMembership;
use Illuminate\Support\Facades\Hash;

it('shows management users page with users list', function () {
    $manager = ManagementUser::query()->create([
        'name' => 'Manager',
        'email' => 'manager-users@example.com',
        'password' => Hash::make('secret123'),
        'role' => 'owner',
        'is_active' => true,
    ]);

    $user = User::factory()->create();
    $workspace = Workspace::factory()->create([
        'owner_user_id' => $user->id,
    ]);

    WorkspaceMembership::factory()->create([
        'workspace_id' => $workspace->id,
        'user_id' => $user->id,
        'is_active' => true,
    ]);

    $this->actingAs($manager, 'management')
        ->get('/management/users')
        ->assertSuccessful();
});
