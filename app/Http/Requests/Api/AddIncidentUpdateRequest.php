<?php

namespace App\Http\Requests\Api;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class AddIncidentUpdateRequest extends FormRequest
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
            'note'         => ['required', 'string', 'max:1000'],
            'action_taken' => [
                'required',
                'string',
                'in:acknowledged,dispatched,suppressed,investigated,escalated,resolved,false_alarm'
            ],
        ];
    }
}