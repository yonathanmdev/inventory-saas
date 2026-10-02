<?php

namespace App\Services;

use App\Models\AuditLogModel;
use Ramsey\Uuid\Uuid;
use Throwable;

class AuditLogger
{
    private AuditLogModel $auditLogModel;

    public function __construct(AuditLogModel $auditLogModel)
    {
        $this->auditLogModel = $auditLogModel;
    }

    /**
     * Create an audit log entry.
     *
     * Supported options:
     *
     * action
     * module
     * table_name
     * record_id
     * record_uuid
     * description
     * old_values
     * new_values
     * business_id
     * status
     */
    public function log(array $data): ?int
    {
        try {
            $action = trim((string) ($data['action'] ?? ''));

            if ($action === '') {
                throw new \InvalidArgumentException(
                    'Audit log action is required.'
                );
            }

            /*
             * Determine business context.
             *
             * If business_id is explicitly supplied, use it.
             *
             * Otherwise use the authenticated user's business.
             *
             * This allows:
             *
             * System Admin:
             *     business_id = NULL
             *
             * System Admin acting on a business resource:
             *     business_id = target business
             *
             * Business User:
             *     business_id = their own business
             */
            $businessId = array_key_exists('business_id', $data)
                ? $this->normalizeNullableInt($data['business_id'])
                : $this->getAuthenticatedBusinessId();

            $userId = array_key_exists('user_id', $data)
                ? $this->normalizeNullableInt($data['user_id'])
                : $this->getAuthenticatedUserId();

            $auditData = [
                'uuid'        => Uuid::uuid7()->toString(),
                'user_id'     => $userId,
                'role_name_at_time' => $_SESSION['role_name'] ?? null,
                'business_id' => $businessId,

                'action'      => $action,
                'module'      => $data['module'] ?? null,
                'table_name'  => $data['table_name'] ?? null,

                'record_id'   => $this->normalizeNullableInt(
                    $data['record_id'] ?? null
                ),

                'record_uuid' => $data['record_uuid'] ?? null,

                'description' => $data['description'] ?? null,

                'old_values'  => $this->encodeValues(
                    $data['old_values'] ?? null
                ),

                'new_values'  => $this->encodeValues(
                    $data['new_values'] ?? null
                ),

                'ip_address'  => $this->getIpAddress(),
                'user_agent'  => $this->getUserAgent(),

                'status'      => $data['status'] ?? 'success',
            ];

            return $this->auditLogModel->create($auditData);

        } catch (Throwable $e) {

            /*
             * Audit logging should not normally break the
             * operation being audited.
             */
            error_log(
                'Audit logging failed: ' . $e->getMessage()
            );

            return null;
        }
    }

    /**
     * Record a successful action.
     */
    public function success(array $data): ?int
    {
        $data['status'] = 'success';

        return $this->log($data);
    }

    /**
     * Record a failed action.
     */
    public function failed(array $data): ?int
    {
        $data['status'] = 'failed';

        return $this->log($data);
    }

    /**
     * Get authenticated user ID.
     */
    private function getAuthenticatedUserId(): ?int
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return null;
        }

        return $this->normalizeNullableInt(
            $_SESSION['user_id'] ?? null
        );
    }

    /**
     * Get authenticated user's business ID.
     *
     * System Admin can legitimately have NULL here.
     */
    private function getAuthenticatedBusinessId(): ?int
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return null;
        }

        return $this->normalizeNullableInt(
            $_SESSION['business_id'] ?? null
        );
    }

    /**
     * Get request IP address.
     */
    private function getIpAddress(): ?string
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? null;

        if (!$ip) {
            return null;
        }

        return substr($ip, 0, 45);
    }

    /**
     * Get request user-agent.
     */
    private function getUserAgent(): ?string
    {
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;

        if (!$userAgent) {
            return null;
        }

        return $userAgent;
    }

    /**
     * Convert values to JSON.
     */
    private function encodeValues(mixed $values): ?string
    {
        if ($values === null) {
            return null;
        }

        /*
         * If the caller already provides JSON,
         * don't encode it a second time.
         */
        if (is_string($values)) {
            return $values;
        }

        return json_encode(
            $values,
            JSON_UNESCAPED_UNICODE |
            JSON_UNESCAPED_SLASHES |
            JSON_THROW_ON_ERROR
        );
    }

    /**
     * Normalize nullable integer values.
     */
    private function normalizeNullableInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (int) $value;
    }
}