<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = App\Models\User::where('role', 'client')->first();
$bookings = App\Models\Booking::where('client_id', $user->id)
    ->get(['id', 'service_type', 'status', 'schedule_start', 'schedule_end']);
echo json_encode($bookings->toArray(), JSON_PRETTY_PRINT);
