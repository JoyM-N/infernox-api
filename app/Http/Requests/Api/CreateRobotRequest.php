<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class CreateRobotRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Only super_admin can provision new robots
        return auth()->user()->hasRole('super_admin');
    }

    public function rules(): array
    {
        return [
            // Serial number must be unique — no two robots can share one
            // regex enforces uppercase letters, numbers and hyphens only
            // e.g. INFERNOX-001 is valid, infernox_001 is not
            'serial_number' => [
                'required',
                'string',
                'max:64',
                'unique:robots,serial_number',
                'regex:/^[A-Z0-9\-]+$/'
            ],

            'name'  => ['required', 'string', 'max:100'],
            'model' => ['required', 'string', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'serial_number.unique' => 'A robot with this serial number already exists.',
            'serial_number.regex'  => 'Serial number must be uppercase letters, numbers and hyphens only. e.g. INFERNOX-001',
        ];
    }
}