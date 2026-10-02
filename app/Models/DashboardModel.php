<?php

namespace App\Models;

use PDO;

class DashboardModel
{
    public function __construct(
        private PDO $db
    ) {
    }


    /**
     * System Administrator dashboard statistics.
     *
     * System Admin sees system-wide information.
     */
    public function getSystemAdminStatistics(): array
    {
        $businessCount = $this->db
            ->query("
                SELECT COUNT(*)
                FROM businesses
            ")
            ->fetchColumn();

        $userCount = $this->db
            ->query("
                SELECT COUNT(*)
                FROM users
            ")
            ->fetchColumn();

        $activeUserCount = $this->db
            ->query("
                SELECT COUNT(*)
                FROM users
                WHERE is_active = 1
            ")
            ->fetchColumn();

        $inactiveUserCount = $this->db
            ->query("
                SELECT COUNT(*)
                FROM users
                WHERE is_active = 0
            ")
            ->fetchColumn();

        $activeBusinessCount = $this->db
            ->query("
                SELECT COUNT(*)
                FROM businesses
                WHERE is_active = 1
            ")
            ->fetchColumn();

        return [
            'business_count'        => (int) $businessCount,
            'active_business_count' => (int) $activeBusinessCount,
            'user_count'            => (int) $userCount,
            'active_user_count'     => (int) $activeUserCount,
            'inactive_user_count'   => (int) $inactiveUserCount,
        ];
    }


    /**
     * Business-user dashboard statistics.
     *
     * IMPORTANT:
     * The business ID comes from the authenticated session.
     *
     * Normal users must never be allowed to submit
     * a business_id to determine which tenant they see.
     */
    public function getBusinessDashboardStatistics(
        int $businessId
    ): array {

        /*
         * Get number of users belonging to this business.
         *
         * This is currently the only business-level statistic
         * we can safely calculate from the tables already confirmed.
         */
        $stmt = $this->db->prepare("
            SELECT COUNT(*)
            FROM users
            WHERE business_id = :business_id
        ");

        $stmt->execute([
            'business_id' => $businessId
        ]);

        $userCount = (int) $stmt->fetchColumn();


        /*
         * Return placeholders for inventory statistics until
         * the products/orders tables are confirmed.
         */
        return [
            'user_count'       => $userCount,
            'total_products'   => 0,
            'low_stock'        => 0,
            'pending_orders'   => 0,
            'revenue_30d'      => 0,
            'recent_activity'  => [],
        ];
    }
}