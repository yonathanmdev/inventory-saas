<?php

namespace App\Controllers;

use App\Helpers\FlashHelper;
use App\Services\BusinessRegistrationService;
use App\Services\RoleService;
use App\Services\RolePermissionService;
class RoleController
{
   public function __construct(
    private RoleService $roleService,
    private BusinessRegistrationService $businessRegistrationService,
    private RolePermissionService $rolePermissionService
) {
}

    public function index($request, $response)
    {
        $pageTitle = 'Roles & Permissions';
        $activeNav = 'roles';

        $roles = $this->roleService->findAll();

        ob_start();

        require __DIR__ . '/../Views/roles/index.php';

        $html = ob_get_clean();

        $response->getBody()->write($html);

        return $response;
    }

    public function create($request, $response)
    {
        $pageTitle = 'Register Role';
        $activeNav = 'roles';

        $businesses = $this
            ->businessRegistrationService
            ->findAll();

        ob_start();

        require __DIR__ . '/../Views/roles/create.php';

        $html = ob_get_clean();

        $response->getBody()->write($html);

        return $response;
    }

    public function store($request, $response)
    {
        $data = $request->getParsedBody();

        $result = $this->roleService->create($data);

        if (!$result['success']) {

            FlashHelper::error(
                $result['message']
            );

            return $response
                ->withHeader(
                    'Location',
                    '/system-admin/roles/create'
                )
                ->withStatus(302);
        }

        FlashHelper::success(
            $result['message']
        );

        return $response
            ->withHeader(
                'Location',
                '/system-admin/roles'
            )
            ->withStatus(302);
    }
    public function permissions($request, $response, array $args)
{
    $roleId = (int) $args['id'];

    $result = $this->rolePermissionService
        ->getAssignmentData($roleId);

    if (!$result['success']) {

        FlashHelper::error(
            $result['message']
        );

        return $response
            ->withHeader(
                'Location',
                '/system-admin/roles'
            )
            ->withStatus(302);
    }

    $pageTitle = 'Assign Permissions';
    $activeNav = 'roles';

    $role = $result['role'];
    $permissions = $result['permissions'];
    $assignedPermissionIds =
        $result['assignedPermissionIds'];

    ob_start();

    require __DIR__ . '/../Views/roles/permissions.php';

    $html = ob_get_clean();

    $response->getBody()->write($html);

    return $response;
}
public function updatePermissions(
    $request,
    $response,
    array $args
) {
    $roleId = (int) $args['id'];

    $data = $request->getParsedBody();

    $permissionIds = $data['permission_ids'] ?? [];

    $result = $this->rolePermissionService->assign(
        $roleId,
        $permissionIds
    );

    if (!$result['success']) {

        FlashHelper::error(
            $result['message']
        );

        return $response
            ->withHeader(
                'Location',
                '/system-admin/roles/' . $roleId . '/permissions'
            )
            ->withStatus(302);
    }

    FlashHelper::success(
        $result['message']
    );

    return $response
        ->withHeader(
            'Location',
            '/system-admin/roles'
        )
        ->withStatus(302);
}
}