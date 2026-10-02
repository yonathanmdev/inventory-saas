<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\BusinessModel;
use Ramsey\Uuid\Uuid;
use Psr\Http\Message\UploadedFileInterface;
use RuntimeException;

class BusinessRegistrationService
{
    public function __construct(
        private BusinessModel $businessModel,
        private AuditLogger $auditLogger,
        private FileUploadService $fileUploadService
    ) {
    }

    public function register(
        array $data,
        ?UploadedFileInterface $logo = null
    ): array {

        $name = trim($data['name'] ?? '');
        $email = trim($data['email'] ?? '');

        if ($name === '') {
            return [
                'success' => false,
                'message' => 'Business name is required.'
            ];
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return [
                'success' => false,
                'message' => 'A valid email is required.'
            ];
        }

        $logoPath = null;

        try {

            /*
             * Upload business logo if provided.
             */
            if (
                $logo !== null
                && $logo->getError() !== UPLOAD_ERR_NO_FILE
            ) {
                $uploaded = $this->fileUploadService->uploadImage(
                    $logo,
                    'businesses/logos'
                );

                $logoPath = $uploaded['path'];
            }

            $businessUuid = Uuid::uuid7()->toString();

            $phone = trim($data['phone'] ?? '');
            $address = trim($data['address'] ?? '');
            $description = trim($data['description'] ?? '');

            $businessData = [
                'uuid'        => $businessUuid,
                'name'        => $name,
                'email'       => $email,
                'phone'       => $phone,
                'address'     => $address,
                'logo_path'   => $logoPath,
                'description' => $description,
                'is_active'   => 1,
            ];

            $businessId = $this->businessModel->create(
                $businessData
            );

            /*
             * Audit successful business creation.
             *
             * Never store the uploaded binary file in the audit log.
             * Store only its relative storage path.
             */
            $this->auditLogger->success([
                'role_name_at_time' =>
                    $_SESSION['role_name'] ?? null,

                'action'      => 'CREATE',
                'module'      => 'Businesses',
                'table_name'  => 'businesses',
                'record_id'   => $businessId,
                'record_uuid' => $businessUuid,
                'business_id' => $businessId,

                'description' =>
                    'Business registered successfully',

                'new_values' => [
                    'uuid'        => $businessUuid,
                    'name'        => $name,
                    'email'       => $email,
                    'phone'       => $phone,
                    'address'     => $address,
                    'logo_path'   => $logoPath,
                    'description' => $description,
                    'is_active'   => 1,
                ],
            ]);

            return [
                'success'     => true,
                'business_id' => $businessId,
                'message'     => 'Business registered successfully.'
            ];

        } catch (\Throwable $e) {

            /*
             * If the file was uploaded but database creation
             * or audit logging failed, remove the orphaned file.
             */
            if ($logoPath !== null) {
                $this->fileUploadService->delete($logoPath);
            }

            throw $e;
        }
    }




    public function findAll(): array
    {
        return $this->businessModel->findAll();
    }


    public function findByUUID(string $uuid): ?array
    {
        return $this->businessModel->findByUUID($uuid);
    }

public function update(
    string $businessUUID,
    array $data,
    ?UploadedFileInterface $logo = null
): array {

    $oldBusiness =
        $this->businessModel->findByUUID($businessUUID);

    if (!$oldBusiness) {
        return [
            'success' => false,
            'message' => 'Business not found.'
        ];
    }

    $businessId =
        (int) $oldBusiness['id'];

    $name =
        trim($data['name'] ?? '');

    $email =
        trim($data['email'] ?? '');

    $phone =
        trim($data['phone'] ?? '');

    $address =
        trim($data['address'] ?? '');

    $description =
        trim($data['description'] ?? '');

    /*
     * Validate business information.
     */
    if ($name === '') {
        return [
            'success' => false,
            'message' => 'Business name is required.'
        ];
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return [
            'success' => false,
            'message' => 'A valid email is required.'
        ];
    }

    /*
     * Keep the existing logo when no new logo
     * has been uploaded.
     */
    $oldLogoPath =
        $oldBusiness['logo_path'] ?? null;

    $newLogoPath =
        $oldLogoPath;

    $newLogoUploaded = false;

    /*
     * Upload the new logo first.
     */
    try {

        if (
            $logo !== null
            && $logo->getError() !== UPLOAD_ERR_NO_FILE
        ) {

            $uploaded =
                $this->fileUploadService->uploadImage(
                    $logo,
                    'businesses/logos'
                );

            $newLogoPath =
                $uploaded['path'];

            $newLogoUploaded = true;
        }

    } catch (\Throwable $e) {

        /*
         * Upload itself failed.
         *
         * FileUploadService normally does not return
         * a path when the upload fails, so there is
         * nothing to clean up here.
         */
        throw $e;
    }

    /*
     * Update the database.
     */
    try {

        $success =
            $this->businessModel->update(
                $businessId,
                [
                    'name'        => $name,
                    'email'       => $email,
                    'phone'       => $phone,
                    'address'     => $address,
                    'logo_path'   => $newLogoPath,
                    'description' => $description,
                ]
            );

        if (!$success) {

            /*
             * Database update failed.
             *
             * The new logo is not referenced by the database,
             * so remove it.
             */
            if (
                $newLogoUploaded
                && $newLogoPath !== null
            ) {
                $this->fileUploadService
                    ->delete($newLogoPath);
            }

            return [
                'success' => false,
                'message' =>
                    'Unable to update the business.'
            ];
        }

    } catch (\Throwable $e) {

        /*
         * Database update threw an exception.
         *
         * Remove the newly uploaded file because the
         * database update did not complete successfully.
         */
        if (
            $newLogoUploaded
            && $newLogoPath !== null
        ) {
            $this->fileUploadService
                ->delete($newLogoPath);
        }

        throw $e;
    }

    /*
     * At this point the database successfully references
     * the new logo.
     *
     * The old logo can now be removed safely.
     */
    if (
        $newLogoUploaded
        && $oldLogoPath
        && $newLogoPath !== $oldLogoPath
    ) {
        $this->fileUploadService
            ->delete($oldLogoPath);
    }

    /*
     * Audit the successful update.
     *
     * Only the logo path is stored in the audit log,
     * never the binary image.
     */
    try {

        $this->auditLogger->success([
            'role_name_at_time' =>
                $_SESSION['role_name'] ?? null,

            'action'      => 'UPDATE',
            'module'      => 'Businesses',
            'table_name'  => 'businesses',
            'record_id'   => $businessId,
            'record_uuid' => $oldBusiness['uuid'],
            'business_id' => $businessId,

            'description' =>
                'Business updated successfully',

            'old_values' => [
                'name' =>
                    $oldBusiness['name'],

                'email' =>
                    $oldBusiness['email'],

                'phone' =>
                    $oldBusiness['phone'],

                'address' =>
                    $oldBusiness['address'],

                'logo_path' =>
                    $oldLogoPath,

                'description' =>
                    $oldBusiness['description'],

                'is_active' =>
                    (int) $oldBusiness['is_active'],
            ],

            'new_values' => [
                'name' =>
                    $name,

                'email' =>
                    $email,

                'phone' =>
                    $phone,

                'address' =>
                    $address,

                'logo_path' =>
                    $newLogoPath,

                'description' =>
                    $description,

                'is_active' =>
                    (int) $oldBusiness['is_active'],
            ],
        ]);

    } catch (\Throwable $e) {

        /*
         * IMPORTANT:
         *
         * Do NOT delete the new logo here.
         *
         * The database has already been updated to reference
         * the new logo. Deleting it would leave a broken path.
         *
         * The business update itself succeeded; only audit
         * logging failed.
         */
        error_log(
            'Business update audit logging failed: '
            . $e->getMessage()
        );
    }

    return [
        'success' => true,
        'message' =>
            'Business updated successfully.'
    ];
}


}

