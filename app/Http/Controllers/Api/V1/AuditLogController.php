<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\AuditLogResource;
use App\Models\AuditLog;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        if (! $request->user()->canManage()) {
            return $this->forbidden('Only managers or owners can view audit logs.');
        }

        $tenantId = $request->user()->tenant_id;
        $perPage  = min((int) $request->get('per_page', 20), 100);

        $query = AuditLog::with('user:id,name')
            ->where('tenant_id', $tenantId);

        if ($v = $request->get('entity_type')) {
            $query->where('entity_type', $v);
        }
        if ($v = $request->get('entity_id')) {
            $query->where('entity_id', (string) $v);
        }
        if ($v = $request->get('action')) {
            $query->where('action', $v);
        }
        if ($v = $request->get('user_id')) {
            $query->where('user_id', (int) $v);
        }
        if ($v = $request->get('date_from')) {
            $query->where('created_at', '>=', $v);
        }
        if ($v = $request->get('date_to')) {
            $query->where('created_at', '<=', $v . ' 23:59:59');
        }

        $logs = $query->orderByDesc('created_at')->paginate($perPage);

        return $this->success(AuditLogResource::collection($logs));
    }
}
