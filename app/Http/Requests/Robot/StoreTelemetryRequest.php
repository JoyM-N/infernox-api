<?php

namespace App\Http\Requests\Robot;

use App\Models\Robot;
use Illuminate\Foundation\Http\FormRequest;

class StoreTelemetryRequest extends FormRequest
{
    public function authorize(): bool
    {
        $actor = auth('sanctum')->user();

        // Robot tokens carry explicit scopes; human operator tokens use ['*']
        // so we must verify the tokenable is a Robot, not just auth + ability.
        return $actor instanceof Robot
            && $actor->tokenCan('telemetry:write');
    }

    public function rules(): array
    {
        return [
            // GPS location — optional because robot may not have
            // a GPS fix yet when it first boots up
            'gps'           => ['nullable', 'array'],
            'gps.lat'       => ['required_with:gps', 'numeric', 'between:-90,90'],
            'gps.lng'       => ['required_with:gps', 'numeric', 'between:-180,180'],

            // Battery 0-100
            'battery'       => ['nullable', 'numeric', 'between:0,100'],

            // Sensor readings
            'temperature'   => ['nullable', 'numeric'],
            'smoke_level'   => ['nullable', 'numeric', 'min:0'],

            // Did the robot's onboard system detect fire?
            'fire_detected' => ['required', 'boolean'],

            // Robot's own clock — when it actually recorded this reading
            'timestamp'     => ['required', 'date'],

            // Optional extra sensor data for fire classification
            // Robot sends these if it has the sensors
            'co_level'      => ['nullable', 'numeric', 'min:0'],
            'gas_type'      => ['nullable', 'string'],
            'smoke_color'   => ['nullable', 'string'],

            // Optional image captured at this moment
            'image_path'    => ['nullable', 'string'],

            'image' => ['nullable', 'file', 'image', 'max:5120'],
        ];
    }

    public function messages(): array
    {
        return [
            'fire_detected.required' => 'fire_detected field is required.',
            'timestamp.required'     => 'Robot timestamp is required.',
            'gps.lat.between'        => 'Latitude must be between -90 and 90.',
            'gps.lng.between'        => 'Longitude must be between -180 and 180.',
        ];
    }
}