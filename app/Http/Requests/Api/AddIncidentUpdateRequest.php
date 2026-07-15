<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class AddIncidentUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()->hasAnyRole(['super_admin', 'operator']);
    }

    public function rules(): array
    {
        return [
            'note'         => ['required', 'string', 'max:1000'],
            'action_taken' => [
                'required',
                'string',
                'in:acknowledged,dispatched,suppressed,investigated,escalated,resolved,false_alarm'
            ],
        ];
    }
}