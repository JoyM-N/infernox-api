<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRobotRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()->hasAnyRole(['super_admin', 'operator']);
    }

    public function rules(): array
    {
        return [
            'name'  => ['sometimes', 'string', 'max:100'],
            'model' => ['sometimes', 'string', 'max:100'],

            // Operators cannot manually set status via API
            // Status is managed by the system (telemetry, heartbeat monitor)
            // That's why status is NOT in this request
        ];
    }
}