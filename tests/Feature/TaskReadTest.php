<?php

namespace Tests\Feature;

use App\Models\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskReadTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_lists_tasks_with_pagination_metadata(): void
    {
        Task::factory()->count(20)->create();

        $this->getJson('/api/tasks')
            ->assertOk()
            ->assertJsonCount(15, 'data')
            ->assertJsonPath('meta.total', 20)
            ->assertJsonPath('meta.per_page', 15)
            ->assertJsonStructure([
                'data' => [['id', 'title', 'description', 'status', 'assignee', 'due_date']],
                'links',
                'meta',
            ]);
    }

    public function test_it_shows_a_single_task(): void
    {
        $task = Task::factory()->create([
            'title' => 'Prepare release',
            'status' => 'in_progress',
            'assignee' => 'Ana',
            'due_date' => '2026-12-01',
        ]);

        $this->getJson("/api/tasks/{$task->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $task->id)
            ->assertJsonPath('data.title', 'Prepare release')
            ->assertJsonPath('data.status', 'in_progress')
            ->assertJsonPath('data.assignee', 'Ana')
            ->assertJsonPath('data.due_date', '2026-12-01');
    }

    public function test_missing_task_returns_json_404_even_without_accept_header(): void
    {
        $this->get('/api/tasks/999')
            ->assertNotFound()
            ->assertHeader('Content-Type', 'application/json')
            ->assertExactJson(['message' => 'Resource not found.']);
    }

    public function test_unknown_api_route_returns_json_404(): void
    {
        $this->get('/api/nope')
            ->assertNotFound()
            ->assertExactJson(['message' => 'Resource not found.']);
    }
}
