<?php

namespace App\Http\Requests\Api;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Validator;

class AssignIncidentRequest extends FormRequest
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
            // Omit to assign yourself. Super admin may assign another operator.
            'operator_id' => ['sometimes', 'integer', 'exists:users,id'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            /** @var User $user */
            $user = Auth::user();
            $incident = $this->route('incident');

            if ($incident->is_locked) {
                $validator->errors()->add('incident', 'This incident is locked and cannot be reassigned.');

                return;
            }

            if ($this->filled('operator_id') && ! $user->hasRole('super_admin')) {
                $validator->errors()->add('operator_id', 'Only a super admin can assign an incident to another operator.');
            }
        });
    }
}
