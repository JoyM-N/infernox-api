<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class SendCommandRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()->hasAnyRole(['super_admin', 'operator']);
    }

    public function rules(): array
    {
        return [
            'command_type' => [
                'required',
                'string',
                'in:move_to,suppress,return_home,activate_siren,stop'
            ],

            // Payload is optional — depends on command type
            'payload'          => ['sometimes', 'array'],

            // move_to requires coordinates
            'payload.lat'      => ['required_if:command_type,move_to', 'numeric'],
            'payload.lng'      => ['required_if:command_type,move_to', 'numeric'],

            // Optional — link command to an incident
            'incident_id'      => ['sometimes', 'uuid', 'exists:incidents,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'payload.lat.required_if' => 'Latitude is required for move_to command.',
            'payload.lng.required_if' => 'Longitude is required for move_to command.',
            'command_type.in'         => 'Invalid command type.',
        ];
    }
}