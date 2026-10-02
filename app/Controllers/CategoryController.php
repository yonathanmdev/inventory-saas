<?php

namespace App\Controllers;

use App\Helpers\AuthHelper;
use App\Helpers\FlashHelper;
use App\Services\CategoryService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class CategoryController
{
    public function __construct(
        private CategoryService $categoryService
    ) {
    }


    /**
     * Category list.
     */
    public function index(
        Request $request,
        Response $response
    ): Response {

        $categories =
            $this->categoryService->findAll();

        ob_start();

        require __DIR__
            . '/../Views/categories/index.php';

        $html = ob_get_clean();

        $response->getBody()->write($html);

        return $response;
    }


    /**
     * Create category form.
     */
    public function create(
        Request $request,
        Response $response
    ): Response {

        $businesses = [];

        $business = null;


        if (AuthHelper::isSystemAdmin()) {

            $businesses =
                $this->categoryService->getBusinesses();

        } else {

            $businessId =
                AuthHelper::businessId();

            if (!$businessId) {

                $response->getBody()->write(
                    'Business account is not associated with a business.'
                );

                return $response->withStatus(403);
            }


            /*
             * Get the business from the available
             * business list.
             *
             * We can replace this later with
             * BusinessModel::findById().
             */
            $businesses =
                $this->categoryService->getBusinesses();

            foreach ($businesses as $item) {

                if ((int) $item['id'] === $businessId) {

                    $business = $item;

                    break;
                }
            }


            if (!$business) {

                $response->getBody()->write(
                    'Business not found.'
                );

                return $response->withStatus(403);
            }
        }


        ob_start();

        require __DIR__
            . '/../Views/categories/create.php';

        $html = ob_get_clean();

        $response->getBody()->write($html);

        return $response;
    }


    /**
     * Store category.
     */
    public function store(
        Request $request,
        Response $response
    ): Response {

        $data =
            $request->getParsedBody();


        /*
         * Business users do NOT control business_id.
         */
        if (!AuthHelper::isSystemAdmin()) {

            $data['business_id'] =
                AuthHelper::businessId();
        }


        $result =
            $this->categoryService->create($data);


        if (!$result['success']) {

            FlashHelper::error(
                $result['message']
            );

            return $response
                ->withHeader(
                    'Location',
                    '/categories/create'
                )
                ->withStatus(302);
        }


        FlashHelper::success(
            $result['message']
        );


        return $response
            ->withHeader(
                'Location',
                '/categories'
            )
            ->withStatus(302);
    }


    /**
     * Edit category form.
     */
    public function edit(
        Request $request,
        Response $response,
        array $args
    ): Response {

        $id =
            (int) ($args['id'] ?? 0);


        if ($id <= 0) {

            return $response
                ->withHeader(
                    'Location',
                    '/categories'
                )
                ->withStatus(302);
        }


        $category =
            $this->categoryService
                ->findForUser($id);


        if (!$category) {

            $response->getBody()->write(
                'Category not found.'
            );

            return $response->withStatus(404);
        }


        ob_start();

        require __DIR__
            . '/../Views/categories/edit.php';

        $html = ob_get_clean();

        $response->getBody()->write($html);

        return $response;
    }


    /**
     * Update category.
     */
    public function update(
        Request $request,
        Response $response,
        array $args
    ): Response {

        $id =
            (int) ($args['id'] ?? 0);


        if ($id <= 0) {

            return $response
                ->withHeader(
                    'Location',
                    '/categories'
                )
                ->withStatus(302);
        }


        $data =
            $request->getParsedBody();

        $result =
            $this->categoryService
                ->update($id, $data);


        if (!$result['success']) {

            FlashHelper::error(
                $result['message']
            );

            return $response
                ->withHeader(
                    'Location',
                    "/categories/{$id}/edit"
                )
                ->withStatus(302);
        }


        FlashHelper::success(
            $result['message']
        );


        return $response
            ->withHeader(
                'Location',
                '/categories'
            )
            ->withStatus(302);
    }
}