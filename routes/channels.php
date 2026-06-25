<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('chat-session.{id}', function ($user, $id) {
    $chatSession = \App\Models\ChatSession::with('booking')->find($id);
    if (!$chatSession) return false;
    
    return $user->id === $chatSession->booking->client_id || 
           $user->id === $chatSession->booking->counselor_id;
});
