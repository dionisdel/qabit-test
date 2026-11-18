<?php

namespace Tests\Feature;

use App\Models\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskWriteTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_a_task(): void
    {
        $due = now()->addWeek()->toDateString();

        $response = $this->postJson('/api/tasks', [
            'title' => 'Write the README',
            'description' => 'Explain how to run it',
            'status' => 'in_progress',
            'assignee' => 'Ana',
            'due_date' => $due,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.title', 'Write the README')
            ->assertJsonPath('data.status', 'in_progress')
            ->assertJsonPath('data.due_date', $due);

        $id = $response->json('data.id');
        $response->assertHeader('Location', url("/api/tasks/{$id}"));
        $this->assertDatabaseHas('tasks', ['id' => $id, 'assignee' => 'Ana']);
    }

    public function test_status_defaults_to_pending_when_creating(): void
    {
        $this->postJson('/api/tasks', ['title' => 'Only a title'])
            ->assertCreated()
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.assignee', null)
            ->assertJsonPath('data.due_date', null);
    }

    public function test_it_rejects_invalid_data_when_creating(): void
    {
        $this->postJson('/api/tasks', [
            'title' => '',
            'status' => 'archived',
            'due_date' => 'tomorrow',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['title', 'status', 'due_date']);

        $this->assertDatabaseCount('tasks', 0);
    }

    public function test_it_rejects_a_due_date_in_the_past_when_creating(): void
    {
        $this->postJson('/api/tasks', [
            'title' => 'Too late',
            'due_date' => now()->subDay()->toDateString(),
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['due_date']);
    }

    public function test_it_rejects_a_title_longer_than_255_characters(): void
    {
        $this->postJson('/api/tasks', ['title' => str_repeat('a', 256)])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['title']);
    }

    public function test_put_updates_a_task(): void
    {
        $task = Task::factory()->create(['title' => 'Old', 'status' => 'pending']);

        $this->putJson("/api/tasks/{$task->id}", [
            'title' => 'New',
            'status' => 'done',
            'assignee' => 'Luis',
        ])
            ->assertOk()
            ->assertJsonPath('data.title', 'New')
            ->assertJsonPath('data.status', 'done')
            ->assertJsonPath('data.assignee', 'Luis');
    }

    public function test_put_requires_a_title(): void
    {
        $task = Task::factory()->create();

        $this->putJson("/api/tasks/{$task->id}", ['status' => 'done'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['title']);
    }

    public function test_patch_updates_only_the_sent_fields(): void
    {
        $task = Task::factory()->create([
            'title' => 'Keep me',
            'status' => 'pending',
            'assignee' => 'Ana',
        ]);

        $this->patchJson("/api/tasks/{$task->id}", ['status' => 'done'])
            ->assertOk()
            ->assertJsonPath('data.status', 'done')
            ->assertJsonPath('data.title', 'Keep me')
            ->assertJsonPath('data.assignee', 'Ana');
    }

    public function test_patch_rejects_an_invalid_status(): void
    {
        $task = Task::factory()->create(['status' => 'pending']);

        $this->patchJson("/api/tasks/{$task->id}", ['status' => 'archived'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['status']);

        $this->assertDatabaseHas('tasks', ['id' => $task->id, 'status' => 'pending']);
    }

    public function test_an_overdue_task_can_still_be_edited(): void
    {
        $task = Task::factory()->create(['due_date' => now()->subWeek()->toDateString()]);

        $this->patchJson("/api/tasks/{$task->id}", [
            'status' => 'done',
            'due_date' => now()->subDays(3)->toDateString(),
        ])->assertOk();
    }

    public function test_updating_a_missing_task_returns_404(): void
    {
        $this->patchJson('/api/tasks/999', ['status' => 'done'])->assertNotFound();
    }

    public function test_it_deletes_a_task(): void
    {
        $task = Task::factory()->create();

        $this->deleteJson("/api/tasks/{$task->id}")->assertNoContent();

        $this->assertDatabaseMissing('tasks', ['id' => $task->id]);
        $this->getJson("/api/tasks/{$task->id}")->assertNotFound();
    }

    public function test_deleting_a_missing_task_returns_404(): void
    {
        $this->deleteJson('/api/tasks/999')->assertNotFound();
    }
}
