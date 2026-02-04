<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlanLimit extends Model
{
    /** @use HasFactory<\Database\Factories\PlanLimitFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'plan_id',
        'limit_key',
        'limit_value',
    ];

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }
}
