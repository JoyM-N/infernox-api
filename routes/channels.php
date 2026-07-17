<?php

use Illuminate\Support\Facades\Broadcast;

// Operations dashboard — any authenticated operator can join
Broadcast::channel('operations.dashboard', function ($user) {
    // Return user info if authorized — this gets sent to the frontend
    return $user->hasAnyRole(['super_admin', 'operator', 'viewer'])
        ? ['id' => $user->id, 'name' => $user->name, 'role' => $user->getRoleNames()->first()]
        : false;
});

// Robot-specific channel — any authenticated user can monitor any robot
Broadcast::channel('robot.{robotId}', function ($user, $robotId) {
    return $user->hasAnyRole(['super_admin', 'operator', 'viewer'])
        ? ['id' => $user->id, 'name' => $user->name]
        : false;
});