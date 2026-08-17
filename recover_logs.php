<?php
require 'C:/xampp/htdocs/Editorial/vendor/autoload.php';
$app = require_once 'C:/xampp/htdocs/Editorial/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\ActivityLog;
use Illuminate\Support\Facades\DB;

$notifications = DB::table('notifications')->orderBy('created_at', 'asc')->get();

foreach ($notifications as $notif) {
    $data = json_decode($notif->data, true);
    $msg = $data['message'] ?? '';
    $url = $data['url'] ?? '';
    
    // Determine action type based on message
    $type = 'system';
    if (strpos($msg, 'مقال جديد') !== false) $type = 'add_article';
    elseif (strpos($msg, 'تعديل مقالك') !== false || strpos($msg, 'تم تعديل') !== false) $type = 'edit_article';
    elseif (strpos($msg, 'تعليق جديد') !== false) $type = 'add_comment';
    elseif (strpos($msg, 'رفض تعليقك') !== false) $type = 'delete_comment';
    
    // Extract target name if any
    $targetName = null;
    if (strpos($msg, ':') !== false) {
        $parts = explode(':', $msg);
        $targetName = trim($parts[1] ?? '');
    }
    
    ActivityLog::create([
        'user_id' => 1,
        'action_type' => $type,
        'action_label' => $msg,
        'target_name' => $targetName,
        'target_url' => $url,
        'created_at' => $notif->created_at,
        'updated_at' => $notif->created_at,
    ]);
}
echo "Recovered activity logs from notifications!\n";
