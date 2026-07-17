<?php

namespace App\Http\Requests\Api;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class UpdateIncidentRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var User|null $user */
        $user = Auth::user();

        return $user?->hasAnyRole(['super_admin', 'operator']) ?? false;
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
