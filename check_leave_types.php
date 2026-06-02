<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$leaveTypes = App\Models\LeaveType::all(['id', 'title']);

echo "Leave Types in Database:\n";
echo "------------------------\n";
foreach ($leaveTypes as $lt) {
    $isPaid = $lt->isPaid() ? 'PAID' : 'UNPAID';
    echo "ID {$lt->id}: {$lt->title} -> {$isPaid}\n";
}
