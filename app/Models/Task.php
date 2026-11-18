<?php

namespace App\Models;

use App\Enums\TaskStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    use HasFactory;

    // Same default as the migration, so a freshly created model already has it.
    protected $attributes = [
        'status' => 'pending',
    ];

    protected $fillable = [
        'title',
        'description',
        'status',
        'assignee',
        'due_date',
    ];

    public function scopeWithStatus(Builder $query, TaskStatus $status): void
    {
        $query->where('status', $status);
    }

    public function scopeAssignedTo(Builder $query, string $assignee): void
    {
        $query->where('assignee', $assignee);
    }

    /**
     * Soonest deadline first, tasks without a deadline last.
     * "id" breaks ties so pagination is stable between requests.
     */
    public function scopeOrderedByDeadline(Builder $query): void
    {
        $query->orderByRaw('due_date IS NULL')
            ->orderBy('due_date')
            ->orderBy('id');
    }

    protected function casts(): array
    {
        return [
            'status' => TaskStatus::class,
            'due_date' => 'date',
        ];
    }
}
