<?php

namespace App\Modules\Auth;

use Illuminate\Support\Facades\DB;

class AuditLogger
{
    public static function log(?int $staffId, string $action, string $entity, ?int $entityId = null, ?array $before = null, ?array $after = null, ?string $ip = null): void
    {
        DB::table('audit_logs')->insert([
            'staff_id' => $staffId, 'action' => $action, 'entity' => $entity, 'entity_id' => $entityId,
            'before' => $before ? json_encode($before, JSON_UNESCAPED_UNICODE) : null,
            'after' => $after ? json_encode($after, JSON_UNESCAPED_UNICODE) : null,
            'ip' => $ip, 'created_at' => now(),
        ]);
    }
}
