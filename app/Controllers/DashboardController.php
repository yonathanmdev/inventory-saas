<?php

namespace App\Controllers;

use App\Models\DashboardModel;

class DashboardController
{
    public function __construct(
        private DashboardModel $dashboardModel
    ) {
    }


    /**
     * Normal business-user dashboard.
     */
    public function index($request, $response)
    {
        /*
         * Normal business users must use the business ID
         * stored in their authenticated session.
         */
        $businessId = (int) ($_SESSION['business_id'] ?? 0);

        if ($businessId <= 0) {
            $response->getBody()->write(
                'Business account is not associated with this user.'
            );

            return $response->withStatus(403);
        }


        $statistics = $this->dashboardModel
            ->getBusinessDashboardStatistics($businessId);


        $totalProducts =
            $statistics['total_products'] ?? 0;

        $lowStockCount =
            $statistics['low_stock'] ?? 0;

        $pendingOrders =
            $statistics['pending_orders'] ?? 0;

        $revenue30d =
            $statistics['revenue_30d'] ?? 0;

        $recentActivity =
            $statistics['recent_activity'] ?? [];


        ob_start();

        require __DIR__ .
            '/../Views/dashboard/index.php';

        $html = ob_get_clean();

        $response->getBody()->write($html);

        return $response;
    }


    /**
     * System Administrator dashboard.
     */
    public function systemAdmin($request, $response)
    {
        $statistics = $this->dashboardModel
            ->getSystemAdminStatistics();


        $businessCount =
            $statistics['business_count'] ?? 0;

        $activeBusinessCount =
            $statistics['active_business_count'] ?? 0;

        $userCount =
            $statistics['user_count'] ?? 0;


        ob_start();

        require __DIR__ .
            '/../Views/dashboard/system-admin.php';

        $html = ob_get_clean();

        $response->getBody()->write($html);

        return $response;
    }
}