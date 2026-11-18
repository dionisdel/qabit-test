<?php

namespace Tests\Feature;

use App\Models\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_filters_by_status(): void
    {
        Task::factory()->count(2)->create(['status' => 'done']);
        Task::factory()->count(3)->create(['status' => 'pending']);

        $response = $this->getJson('/api/tasks?status=done')->assertOk();

        $this->assertCount(2, $response->json('data'));
        $this->assertSame(['done'], collect($response->json('data'))->pluck('status')->unique()->all());
    }

    public function test_it_filters_by_assignee(): void
    {
        Task::factory()->count(2)->create(['assignee' => 'Ana']);
        Task::factory()->count(3)->create(['assignee' => 'Luis']);

        $response = $this->getJson('/api/tasks?assignee=Ana')->assertOk();

        $this->assertCount(2, $response->json('data'));
        $this->assertSame(['Ana'], collect($response->json('data'))->pluck('assignee')->unique()->all());
    }

    public function test_it_combines_status_and_assignee_filters(): void
    {
        Task::factory()->create(['status' => 'done', 'assignee' => 'Ana']);
        Task::factory()->create(['status' => 'pending', 'assignee' => 'Ana']);
        Task::factory()->create(['status' => 'done', 'assignee' => 'Luis']);

        $this->getJson('/api/tasks?status=done&assignee=Ana')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('meta.total', 1);
    }

    public function test_filters_with_no_matches_return_an_empty_page(): void
    {
        Task::factory()->create(['assignee' => 'Ana']);

        $this->getJson('/api/tasks?assignee=Nobody')
            ->assertOk()
            ->assertJsonCount(0, 'data')
            ->assertJsonPath('meta.total', 0);
    }

    public function test_it_paginates_with_per_page_and_page(): void
    {
        Task::factory()->count(5)->create();

        $this->getJson('/api/tasks?per_page=2&page=3')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('meta.current_page', 3)
            ->assertJsonPath('meta.last_page', 3)
            ->assertJsonPath('meta.total', 5);
    }

    public function test_pagination_links_keep_the_active_filters(): void
    {
        Task::factory()->count(5)->create(['status' => 'done']);

        $next = $this->getJson('/api/tasks?status=done&per_page=2')->json('links.next');

        $this->assertStringContainsString('status=done', $next);
        $this->assertStringContainsString('per_page=2', $next);
    }

    public function test_it_orders_by_deadline_with_undated_tasks_last(): void
    {
        $later = Task::factory()->create(['due_date' => '2027-03-01']);
        $undated = Task::factory()->create(['due_date' => null]);
        $sooner = Task::factory()->create(['due_date' => '2027-01-01']);

        $ids = collect($this->getJson('/api/tasks')->json('data'))->pluck('id')->all();

        $this->assertSame([$sooner->id, $later->id, $undated->id], $ids);
    }

    public function test_it_rejects_an_unknown_status_filter(): void
    {
        $this->getJson('/api/tasks?status=archived')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['status']);
    }

    public function test_it_rejects_an_out_of_range_per_page(): void
    {
        $this->getJson('/api/tasks?per_page=101')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['per_page']);

        $this->getJson('/api/tasks?per_page=0')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['per_page']);
    }
}
