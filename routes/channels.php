<?php

use App\Models\ProcessInstance;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('system.instances', function ($user) {
    return $user->hasAnyRole(['admin', 'manager', 'process_designer']);
});

Broadcast::channel('instance.{instanceId}', function ($user, $instanceId) {
    $instance = ProcessInstance::find($instanceId);

    return $instance ? $user->can('view', $instance) : false;
});

Broadcast::channel('user.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});
