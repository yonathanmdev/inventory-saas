<?php

namespace App\Services;

use App\Helpers\AuthHelper;
use App\Models\CategoryModel;
use Ramsey\Uuid\Uuid;

class CategoryService
{
    public function __construct(
        private CategoryModel $categoryModel,
        private BusinessRegistrationService $businessRegistrationService,
        private AuditLogger $auditLogger
    ) {
    }


    /**
     * Get categories according to the logged-in user.
     */
    public function findAll(): array
    {
        if (AuthHelper::isSystemAdmin()) {
            return $this->categoryModel->findAllWithBusiness();
        }

        $businessId = AuthHelper::businessId();

        if (!$businessId) {
            return [];
        }

        return $this->categoryModel
            ->findAllByBusinessId($businessId);
    }


    /**
     * Get businesses for System Admin.
     */
    public function getBusinesses(): array
    {
        return $this->businessRegistrationService->findAll();
    }


    /**
     * Get a single category.
     */
    public function findForUser(int $id): ?array
    {
        if (AuthHelper::isSystemAdmin()) {
            return $this->categoryModel->findById($id);
        }

        $businessId = AuthHelper::businessId();

        if (!$businessId) {
            return null;
        }

        return $this->categoryModel->findByIdAndBusiness(
            $id,
            $businessId
        );
    }


    /**
     * Register a category.
     */
    public function create(array $data): array
    {
        $name = trim($data['name'] ?? '');

        $description = trim(
            $data['description'] ?? ''
        );

        if ($name === '') {
            return [
                'success' => false,
                'message' => 'Category name is required.'
            ];
        }


        /*
         * Determine business.
         *
         * System Admin:
         *   Use selected business.
         *
         * Business User:
         *   ALWAYS use authenticated business.
         */
        if (AuthHelper::isSystemAdmin()) {

            $businessId = isset($data['business_id'])
                ? (int) $data['business_id']
                : 0;

        } else {

            $businessId = AuthHelper::businessId() ?? 0;
        }


        if ($businessId <= 0) {
            return [
                'success' => false,
                'message' => 'Business is required.'
            ];
        }


        /*
         * Make sure the business exists.
         */
        $businesses =
            $this->businessRegistrationService->findAll();

        $businessExists = false;

        foreach ($businesses as $business) {

            if ((int) $business['id'] === $businessId) {
                $businessExists = true;
                break;
            }
        }

        if (!$businessExists) {
            return [
                'success' => false,
                'message' => 'Selected business was not found.'
            ];
        }


        /*
         * Check duplicate category.
         */
        if (
            $this->categoryModel->findByName(
                $name,
                $businessId
            )
        ) {
            return [
                'success' => false,
                'message' =>
                    'This category already exists for this business.'
            ];
        }


        /*
         * Generate category UUID once so the same UUID
         * can be stored in the database and audit log.
         */
        $categoryUuid = Uuid::uuid7()->toString();

        $isActive = isset($data['is_active']) ? 1 : 0;

        $categoryId = $this->categoryModel->create([
            'uuid' => $categoryUuid,
            'business_id' => $businessId,
            'name' => $name,
            'description' =>
                $description !== ''
                    ? $description
                    : null,
            'is_active' => $isActive
        ]);


        /*
         * Audit CREATE.
         *
         * The business_id is explicitly supplied because
         * System Admin may create a category for another business.
         */
        $this->auditLogger->success([
            'action' => 'CREATE',
            'module' => 'Categories',
            'table_name' => 'categories',
            'record_id' => $categoryId,
            'record_uuid' => $categoryUuid,
            'business_id' => $businessId,
            'description' => 'Category registered successfully',
            'new_values' => [
                'uuid' => $categoryUuid,
                'business_id' => $businessId,
                'name' => $name,
                'description' =>
                    $description !== ''
                        ? $description
                        : null,
                'is_active' => $isActive,
            ],
        ]);


        return [
            'success' => true,
            'category_id' => $categoryId,
            'message' => 'Category registered successfully.'
        ];
    }


    /**
     * Update a category.
     */
    public function update(
        int $id,
        array $data
    ): array {

        $category = $this->findForUser($id);

        if (!$category) {
            return [
                'success' => false,
                'message' => 'Category not found.'
            ];
        }


        $name = trim($data['name'] ?? '');

        $description = trim(
            $data['description'] ?? ''
        );


        if ($name === '') {
            return [
                'success' => false,
                'message' => 'Category name is required.'
            ];
        }


        /*
         * Never change the category's business
         * through the edit form.
         */
        $businessId = (int) $category['business_id'];


        if (
            $this->categoryModel->findByName(
                $name,
                $businessId,
                $id
            )
        ) {
            return [
                'success' => false,
                'message' =>
                    'Another category with this name already exists.'
            ];
        }


        $isActive = isset($data['is_active']) ? 1 : 0;

        /*
         * Keep the original category UUID.
         */
        $categoryUuid = $category['uuid'] ?? null;


        /*
         * Capture the old values before updating.
         */
        $oldValues = [
            'uuid' => $category['uuid'] ?? null,
            'business_id' => $businessId,
            'name' => $category['name'] ?? null,
            'description' => $category['description'] ?? null,
            'is_active' => isset($category['is_active'])
                ? (int) $category['is_active']
                : null,
        ];


        $newValues = [
            'uuid' => $categoryUuid,
            'business_id' => $businessId,
            'name' => $name,
            'description' =>
                $description !== ''
                    ? $description
                    : null,
            'is_active' => $isActive,
        ];


        $updated =
            $this->categoryModel->update(
                $id,
                [
                    'business_id' => $businessId,
                    'name' => $name,
                    'description' =>
                        $description !== ''
                            ? $description
                            : null,
                    'is_active' => $isActive
                ]
            );


        if (!$updated) {
            return [
                'success' => false,
                'message' => 'Unable to update category.'
            ];
        }


        /*
         * Audit UPDATE only after the database update succeeds.
         */
        $this->auditLogger->success([
            'action' => 'UPDATE',
            'module' => 'Categories',
            'table_name' => 'categories',
            'record_id' => $id,
            'record_uuid' => $categoryUuid,
            'business_id' => $businessId,
            'description' => 'Category updated successfully',
            'old_values' => $oldValues,
            'new_values' => $newValues,
        ]);


        return [
            'success' => true,
            'message' => 'Category updated successfully.'
        ];
    }
}