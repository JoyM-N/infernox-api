<?php

namespace App\Http\Requests\Api;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class UpdateRobotRequest extends FormRequest
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
            'name'  => ['sometimes', 'string', 'max:100'],
            'model' => ['sometimes', 'string', 'max:100'],

            // Operators cannot manually set status via API
            // Status is managed by the system (telemetry, heartbeat monitor)
            // That's why status is NOT in this request
        ];
    }
}