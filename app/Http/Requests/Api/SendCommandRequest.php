<?php

namespace App\Http\Requests\Api;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class SendCommandRequest extends FormRequest
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
            'command_type' => [
                'required',
                'string',
                'in:move_to,suppress,return_home,activate_siren,stop,emergency_stop,drive,arm_joint',
            ],

            'payload' => ['sometimes', 'array'],

            // move_to requires coordinates
            'payload.lat' => ['required_if:command_type,move_to', 'numeric'],
            'payload.lng' => ['required_if:command_type,move_to', 'numeric'],

            // Manual drive teleop from dashboard
            'payload.direction' => [
                'required_if:command_type,drive',
                'string',
                'in:forward,reverse,left,right,stop',
            ],

            // Arm joint teleop from dashboard
            'payload.joint' => [
                'required_if:command_type,arm_joint',
                'integer',
                'in:1,2',
            ],
            'payload.action' => [
                'required_if:command_type,arm_joint',
                'string',
                'in:up,down,stop',
            ],

            // Optional — link command to an incident
            'incident_id' => ['sometimes', 'uuid', 'exists:incidents,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'payload.lat.required_if'       => 'Latitude is required for move_to command.',
            'payload.lng.required_if'       => 'Longitude is required for move_to command.',
            'payload.direction.required_if' => 'Direction is required for drive command.',
            'payload.direction.in'          => 'Direction must be forward, reverse, left, right, or stop.',
            'payload.joint.required_if'     => 'Joint number is required for arm_joint command.',
            'payload.joint.in'              => 'Joint must be 1 or 2.',
            'payload.action.required_if'    => 'Action is required for arm_joint command.',
            'payload.action.in'             => 'Action must be up, down, or stop.',
            'command_type.in'               => 'Invalid command type.',
        ];
    }
}
