<?php

namespace App\Http\Requests;

use App\Enums\TaskStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * PUT requires the title; PATCH only validates the fields that are sent.
     * Fields that are not sent keep their current value.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $presence = $this->isMethod('PUT') ? 'required' : 'sometimes';

        return [
            'title' => [$presence, 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'status' => ['sometimes', Rule::enum(TaskStatus::class)],
            'assignee' => ['nullable', 'string', 'max:255'],
            // No "not in the past" rule here: overdue tasks must remain editable.
            'due_date' => ['nullable', 'date_format:Y-m-d'],
        ];
    }
}
