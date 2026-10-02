<?php

namespace App\Controllers;

use App\Helpers\FlashHelper;
use App\Services\BusinessRegistrationService;

class BusinessController
{
    public function __construct(
        private BusinessRegistrationService $businessRegistrationService
    ) {
    }

    /**
     * Display businesses.
     */
    public function index($request, $response)
    {
        $pageTitle = 'Businesses';
        $activeNav = 'businesses';

        $businesses = $this->businessRegistrationService->findAll();

        ob_start();

        require __DIR__ . '/../Views/businesses/index.php';

        $html = ob_get_clean();

        $response->getBody()->write($html);

        return $response;
    }

    /**
     * Display business registration form.
     */
    public function create($request, $response)
    {
        $pageTitle = 'Register Business';
        $activeNav = 'businesses';

        ob_start();

        require __DIR__ . '/../Views/businesses/create.php';

        $html = ob_get_clean();

        $response->getBody()->write($html);

        return $response;
    }

    /**
     * Store a new business.
     *
     * CSRF validation is handled by CsrfMiddleware.
     */

public function store($request, $response)
{
    $data = $request->getParsedBody() ?? [];

    /*
     * Get uploaded files.
     */
    $uploadedFiles = $request->getUploadedFiles();

    $logo = $uploadedFiles['logo'] ?? null;

    try {

        $result =
            $this->businessRegistrationService->register(
                $data,
                $logo
            );

        if (!$result['success']) {

            FlashHelper::error(
                $result['message']
            );

            return $response
                ->withHeader(
                    'Location',
                    '/system-admin/businesses/create'
                )
                ->withStatus(302);
        }

        FlashHelper::success(
            $result['message']
        );

        return $response
            ->withHeader(
                'Location',
                '/system-admin/businesses'
            )
            ->withStatus(302);

    } catch (\Throwable $e) {

        error_log((string) $e);

        FlashHelper::error(
            'Unable to register the business. Please try again.'
        );

        return $response
            ->withHeader(
                'Location',
                '/system-admin/businesses/create'
            )
            ->withStatus(302);
    }
}

public function update($request, $response, array $args)
{
    $uuid = trim($args['id'] ?? '');

    if ($uuid === '') {

        FlashHelper::error(
            'Invalid business identifier.'
        );

        return $response
            ->withHeader(
                'Location',
                '/system-admin/businesses'
            )
            ->withStatus(302);
    }

    $data =
        $request->getParsedBody() ?? [];

    $uploadedFiles =
        $request->getUploadedFiles();

    $logo =
        $uploadedFiles['logo'] ?? null;

    try {

        $result =
            $this->businessRegistrationService->update(
                $uuid,
                [
                    'name' =>
                        trim($data['name'] ?? ''),

                    'email' =>
                        trim($data['email'] ?? ''),

                    'phone' =>
                        trim($data['phone'] ?? ''),

                    'address' =>
                        trim($data['address'] ?? ''),

                    'description' =>
                        trim($data['description'] ?? ''),
                ],
                $logo
            );

        if (!$result['success']) {

            FlashHelper::error(
                $result['message']
            );

            return $response
                ->withHeader(
                    'Location',
                    "/system-admin/businesses/{$uuid}/edit"
                )
                ->withStatus(302);
        }

        FlashHelper::success(
            $result['message']
        );

        return $response
            ->withHeader(
                'Location',
                '/system-admin/businesses'
            )
            ->withStatus(302);

    } catch (\Throwable $e) {

        error_log((string) $e);

        FlashHelper::error(
            'Unable to update the business. Please try again.'
        );

        return $response
            ->withHeader(
                'Location',
                "/system-admin/businesses/{$uuid}/edit"
            )
            ->withStatus(302);
    }
}


    /**
     * Display business edit form.
     */
    public function edit($request, $response, array $args)
    {
        $uuid = trim($args['id'] ?? '');

        if ($uuid === '') {
            FlashHelper::error('Invalid business identifier.');

            return $response
                ->withHeader(
                    'Location',
                    '/system-admin/businesses'
                )
                ->withStatus(302);
        }

        $business = $this->businessRegistrationService
            ->findByUUID($uuid);

        if (!$business) {
            FlashHelper::error('Business not found.');

            return $response
                ->withHeader(
                    'Location',
                    '/system-admin/businesses'
                )
                ->withStatus(302);
        }

        $pageTitle = 'Edit Business';
        $activeNav = 'businesses';

        ob_start();

        require __DIR__ . '/../Views/businesses/edit.php';

        $html = ob_get_clean();

        $response->getBody()->write($html);

        return $response;
    }
public function logo($request, $response, array $args)
{
    $uuid = trim($args['uuid'] ?? '');

    if ($uuid === '') {
        return $response->withStatus(404);
    }

    $business =
        $this->businessRegistrationService
            ->findByUUID($uuid);

    if (!$business) {
        return $response->withStatus(404);
    }

    $logoPath =
        $business['logo_path'] ?? null;

    if (!$logoPath) {
        return $response->withStatus(404);
    }

    $filePath =
        dirname(__DIR__, 2)
        . '/storage/'
        . ltrim($logoPath, '/');

    if (
        !is_file($filePath)
        || !is_readable($filePath)
    ) {
        return $response->withStatus(404);
    }

    $mimeType =
        mime_content_type($filePath);

    $allowedMimeTypes = [
        'image/jpeg',
        'image/png',
        'image/webp',
    ];

    if (!in_array($mimeType, $allowedMimeTypes, true)) {
        return $response->withStatus(404);
    }

    $contents =
        file_get_contents($filePath);

    if ($contents === false) {
        return $response->withStatus(404);
    }

    $response =
        $response->withHeader(
            'Content-Type',
            $mimeType
        );

    $response =
        $response->withHeader(
            'Content-Length',
            (string) filesize($filePath)
        );

    $response =
        $response->withHeader(
            'Cache-Control',
            'private, max-age=3600'
        );

    $response->getBody()->write($contents);

    return $response;
}


}

