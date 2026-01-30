<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, TwoFactorAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function assignedTasks(): BelongsToMany
    {
        return $this->belongsToMany(Task::class)
            ->withPivot(['assigned_by_user_id', 'assigned_at'])
            ->withTimestamps();
    }

    public function taskBoards(): HasMany
    {
        return $this->hasMany(TaskBoard::class);
    }

    public function taskColumns(): HasMany
    {
        return $this->hasMany(TaskColumn::class);
    }

    public function taskLabels(): HasMany
    {
        return $this->hasMany(TaskLabel::class);
    }

    public function taskTags(): HasMany
    {
        return $this->hasMany(TaskTag::class);
    }

    public function ownedWorkspaces(): HasMany
    {
        return $this->hasMany(Workspace::class, 'owner_user_id');
    }

    public function workspaceMemberships(): HasMany
    {
        return $this->hasMany(WorkspaceMembership::class);
    }

    public function workspaces(): BelongsToMany
    {
        return $this->belongsToMany(Workspace::class, 'workspace_memberships')
            ->withPivot(['role', 'weekly_capacity_minutes', 'is_active', 'joined_at'])
            ->withTimestamps();
    }

    public function timeEntries(): HasMany
    {
        return $this->hasMany(TimeEntry::class);
    }

    public function workspaceRole(int $workspaceId): ?string
    {
        return $this->workspaceMemberships()
            ->where('workspace_id', $workspaceId)
            ->value('role');
    }

    /**
     * @param  array<int, string>  $roles
     */
    public function hasWorkspaceRole(int $workspaceId, array $roles): bool
    {
        $role = $this->workspaceRole($workspaceId);

        return $role ? in_array($role, $roles, true) : false;
    }
}
