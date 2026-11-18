<?php

namespace Tests\Unit;

use App\Enums\TaskStatus;
use App\Models\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_status_defaults_to_pending_and_is_cast_to_enum(): void
    {
        $task = Task::create(['title' => 'Write tests'])->fresh();

        $this->assertSame(TaskStatus::Pending, $task->status);
        $this->assertNull($task->assignee);
        $this->assertNull($task->due_date);
    }

    public function test_due_date_is_cast_to_a_date(): void
    {
        $task = Task::factory()->create(['due_date' => '2026-12-31'])->fresh();

        $this->assertSame('2026-12-31', $task->due_date->toDateString());
    }
}
