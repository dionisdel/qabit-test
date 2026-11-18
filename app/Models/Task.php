<?php

namespace App\Models;

use App\Enums\TaskStatus;
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

    protected function casts(): array
    {
        return [
            'status' => TaskStatus::class,
            'due_date' => 'date',
        ];
    }
}
