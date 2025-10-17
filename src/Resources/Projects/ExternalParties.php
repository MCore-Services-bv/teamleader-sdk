<?php

namespace McoreServices\TeamleaderSDK\Resources\Projects;

use InvalidArgumentException;
use McoreServices\TeamleaderSDK\Resources\Resource;

class ExternalParties extends Resource
{
    protected string $description = 'Manage external parties on projects in Teamleader Focus';

    // Resource capabilities - External parties have specific add/update/delete operations
    protected bool $supportsCreation = false; // Uses addToProject instead

    protected bool $supportsUpdate = false;   // Custom update method

    protected bool $supportsDeletion = false; // Custom delete method

    protected bool $supportsBatch = false;

    protected bool $supportsPagination = false;

    protected bool $supportsSorting = false;

    protected bool $supportsFiltering = false;

    protected bool $supportsSideloading = false;

    // Available includes for sideloading (none based on API docs)
    protected array $availableIncludes = [];

    // Default includes
    protected array $defaultIncludes = [];

    // Common filters (none for external parties)
    protected array $commonFilters = [];

    // Available customer types
    protected array $customerTypes = [
        'contact',
        'company',
    ];

    // Usage examples specific to external parties
    protected array $usageExamples = [
        'add_contact_to_project' => [
            'description' => 'Add a contact as external party to a project',
            'code' => '$result = $teamleader->externalParties()->addToProject(
    "project-uuid",
    "contact",
    "contact-uuid",
    "Project Manager"
);',
        ],
        'add_company_to_project' => [
            'description' => 'Add a company as external party to a project',
            'code' => '$result = $teamleader->externalParties()->addToProject(
    "project-uuid",
    "company",
    "company-uuid",
    "Contractor",
    "Lead Contractor"
);',
        ],
        'add_with_array' => [
            'description' => 'Add external party with full array structure',
            'code' => '$result = $teamleader->externalParties()->addToProject([
    "project_id" => "project-uuid",
    "customer" => [
        "type" => "contact",
        "id" => "contact-uuid"
    ],
    "function" => "Project Manager",
    "sub_function" => "Senior PM"
]);',
        ],
        'update_external_party' => [
            'description' => 'Update an external party',
            'code' => '$result = $teamleader->externalParties()->update(
    "external-party-uuid",
    [
        "customer" => [
            "type" => "contact",
            "id" => "contact-uuid"
        ],
        "function" => "Lead Designer",
        "sub_function" => null
    ]
);',
        ],
        'delete_external_party' => [
            'description' => 'Delete an external party',
            'code' => '$result = $teamleader->externalParties()->delete("external-party-uuid");',
        ],
    ];

    /**
     * Get the base path for the external parties resource
     */
    protected function getBasePath(): string
    {
        return 'projects-v2/externalParties';
    }

    /**
     * Add an external party to a project
     *
     * Can be called with individual parameters or with a data array:
     * - addToProject($projectId, $customerType, $customerId, $function, $subFunction)
     * - addToProject($dataArray)
     *
     * @param  string|array  $projectIdOrData  Project UUID or full data array
     * @param  string|null  $customerType  Customer type (contact, company) - required if using individual params
     * @param  string|null  $customerId  Customer UUID - required if using individual params
     * @param  string|null  $function  Function/role description
     * @param  string|null  $subFunction  Sub-function/role description
     *
     * @throws InvalidArgumentException
     */
    public function addToProject(
        $projectIdOrData,
        ?string $customerType = null,
        ?string $customerId = null,
        ?string $function = null,
        ?string $subFunction = null
    ): array {
        // Support both array and individual parameters
        if (is_array($projectIdOrData)) {
            $data = $projectIdOrData;
        } else {
            if (! $customerType || ! $customerId) {
                throw new InvalidArgumentException(
                    'When using individual parameters, projectId, customerType, and customerId are required'
                );
            }

            $data = [
                'project_id' => $projectIdOrData,
                'customer' => [
                    'type' => $customerType,
                    'id' => $customerId,
                ],
            ];

            if ($function !== null) {
                $data['function'] = $function;
            }

            if ($subFunction !== null) {
                $data['sub_function'] = $subFunction;
            }
        }

        // Validate required fields
        if (empty($data['project_id'])) {
            throw new InvalidArgumentException('project_id is required');
        }

        if (empty($data['customer']['type']) || empty($data['customer']['id'])) {
            throw new InvalidArgumentException('customer.type and customer.id are required');
        }

        // Validate customer type
        if (! in_array($data['customer']['type'], $this->customerTypes)) {
            throw new InvalidArgumentException(
                'Invalid customer type. Must be one of: '.implode(', ', $this->customerTypes)
            );
        }

        return $this->api->request('POST', $this->getBasePath().'.addToProject', $data);
    }

    /**
     * Update an external party
     *
     * @param  string  $id  External party UUID
     * @param  array  $data  Update data containing customer, function, and/or sub_function
     *
     * @throws InvalidArgumentException
     */
    public function update($id, array $data): array
    {
        if (empty($id)) {
            throw new InvalidArgumentException('External party ID is required');
        }

        // Ensure ID is in the data
        $data['id'] = $id;

        // Validate customer structure if provided
        if (isset($data['customer'])) {
            if (empty($data['customer']['type']) || empty($data['customer']['id'])) {
                throw new InvalidArgumentException('customer.type and customer.id are required when updating customer');
            }

            if (! in_array($data['customer']['type'], $this->customerTypes)) {
                throw new InvalidArgumentException(
                    'Invalid customer type. Must be one of: '.implode(', ', $this->customerTypes)
                );
            }
        }

        return $this->api->request('POST', $this->getBasePath().'.update', $data);
    }

    /**
     * Delete an external party
     *
     * @param  string  $id  External party UUID
     * @param  mixed  ...$additionalParams  Not used for external parties
     *
     * @throws InvalidArgumentException
     */
    public function delete($id, ...$additionalParams): array
    {
        if (empty($id)) {
            throw new InvalidArgumentException('External party ID is required');
        }

        return $this->api->request('POST', $this->getBasePath().'.delete', ['id' => $id]);
    }

    /**
     * Remove an external party from a project (alias for delete)
     *
     * @param  string  $id  External party UUID
     */
    public function removeFromProject(string $id): array
    {
        return $this->delete($id);
    }

    /**
     * Update the function/role of an external party
     *
     * @param  string  $id  External party UUID
     * @param  string|null  $function  Function/role
     * @param  string|null  $subFunction  Sub-function/role
     */
    public function updateRole(string $id, ?string $function = null, ?string $subFunction = null): array
    {
        $data = [];

        if ($function !== null) {
            $data['function'] = $function;
        }

        if ($subFunction !== null) {
            $data['sub_function'] = $subFunction;
        }

        return $this->update($id, $data);
    }
}
