<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class UpdateIncidentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()->hasAnyRole(['super_admin', 'operator']);
    }

    public function rules(): array
    {
        return [
            'status' => [
                'sometimes',
                'string',
                'in:open,investigating,suppressing,resolved,false_alarm'
            ],
            'severity' => [
                'sometimes',
                'string',
                'in:low,medium,high,critical'
            ],
            'fire_type' => [
                'sometimes',
                'string',
                'in:class_a,class_b,class_c,class_d,class_f,unknown'
            ],
        ];
    }
}