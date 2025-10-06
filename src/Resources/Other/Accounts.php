<?php

namespace McoreServices\TeamleaderSDK\Resources\Other;

use InvalidArgumentException;
use McoreServices\TeamleaderSDK\Resources\Resource;

class Accounts extends Resource
{
    protected string $description = 'Fetch account information such as Projects version status';

    // Resource capabilities - Accounts only supports status checking
    protected bool $supportsCreation = false;
    protected bool $supportsUpdate = false;
    protected bool $supportsDeletion = false;
    protected bool $supportsBatch = false;
    protected bool $supportsPagination = false;
    protected bool $supportsSorting = false;
    protected bool $supportsFiltering = false;
    protected bool $supportsSideloading = false;

    // Available includes for sideloading (none for accounts)
    protected array $availableIncludes = [];

    // Default includes
    protected array $defaultIncludes = [];

    // Common filters (none for accounts)
    protected array $commonFilters = [];

    // Valid project version statuses
    protected array $projectVersions = [
        'projects-v2',
        'legacy',
    ];

    // Usage examples specific to accounts
    protected array $usageExamples = [
        'check_projects_version' => [
            'description' => 'Check which Projects version the account is using',
            'code' => '$status = $teamleader->accounts()->projectsV2Status();
                $version = $status["data"]["status"]; // "projects-v2" or "legacy"'
        ],
        'check_auto_switch_date' => [
            'description' => 'Check if account will be automatically switched to Projects v2',
            'code' => '$status = $teamleader->accounts()->projectsV2Status();

            if (isset($status["data"]["will_be_automatically_switched_on"])) {
                $switchDate = $status["data"]["will_be_automatically_switched_on"];
                echo "Account will be switched on: {$switchDate}";
            }'
        ],
        'is_using_projects_v2' => [
            'description' => 'Check if account is using Projects v2',
            'code' => '$isV2 = $teamleader->accounts()->isUsingProjectsV2();

            if ($isV2) {
                // Use Projects v2 endpoints
            } else {
                // Use legacy project endpoints
            }'
        ],
        'conditional_logic' => [
            'description' => 'Use different logic based on Projects version',
            'code' => '$accounts = $teamleader->accounts();

            if ($accounts->isUsingProjectsV2()) {
                $projects = $teamleader->projects()->list();
            } else {
                // Handle legacy projects
                $projects = $teamleader->legacyProjects()->list();
            }'
        ],
    ];

    /**
     * Get the base path for the accounts resource
     */
    protected function getBasePath(): string
    {
        return 'accounts';
    }

    /**
     * Fetch which version of Projects the account is using
     *
     * @return array Response containing status and optional auto-switch date
     */
    public function projectsV2Status(): array
    {
        return $this->api->request('POST', $this->getBasePath() . '.projects-v2-status');
    }

    /**
     * Check if the account is using Projects v2
     *
     * @return bool True if using projects-v2, false if using legacy
     */
    public function isUsingProjectsV2(): bool
    {
        $status = $this->projectsV2Status();
        return $status['data']['status'] === 'projects-v2';
    }

    /**
     * Check if the account is using legacy Projects
     *
     * @return bool True if using legacy, false if using projects-v2
     */
    public function isUsingLegacyProjects(): bool
    {
        return !$this->isUsingProjectsV2();
    }

    /**
     * Get the current Projects version status
     *
     * @return string Either "projects-v2" or "legacy"
     */
    public function getProjectsVersion(): string
    {
        $status = $this->projectsV2Status();
        return $status['data']['status'];
    }

    /**
     * Get the date when account will be automatically switched to Projects v2
     *
     * @return string|null Date string (YYYY-MM-DD) or null if not scheduled
     */
    public function getAutoSwitchDate(): ?string
    {
        $status = $this->projectsV2Status();
        return $status['data']['will_be_automatically_switched_on'] ?? null;
    }

    /**
     * Check if account is scheduled for automatic switch to Projects v2
     *
     * @return bool True if auto-switch is scheduled
     */
    public function hasScheduledAutoSwitch(): bool
    {
        return $this->getAutoSwitchDate() !== null;
    }

    /**
     * Get complete account status information
     *
     * @return array Formatted array with all status information
     */
    public function getAccountStatus(): array
    {
        $status = $this->projectsV2Status();
        $data = $status['data'];

        return [
            'version' => $data['status'],
            'is_projects_v2' => $data['status'] === 'projects-v2',
            'is_legacy' => $data['status'] === 'legacy',
            'auto_switch_date' => $data['will_be_automatically_switched_on'] ?? null,
            'has_scheduled_switch' => isset($data['will_be_automatically_switched_on']),
        ];
    }

    /**
     * Get days until automatic switch (if scheduled)
     *
     * @return int|null Number of days until switch, or null if not scheduled
     */
    public function getDaysUntilAutoSwitch(): ?int
    {
        $switchDate = $this->getAutoSwitchDate();

        if ($switchDate === null) {
            return null;
        }

        $now = new \DateTime();
        $switch = new \DateTime($switchDate);
        $diff = $now->diff($switch);

        // Return negative if date has passed
        return $diff->invert ? -$diff->days : $diff->days;
    }

    /**
     * Check if auto-switch date is approaching (within specified days)
     *
     * @param int $days Number of days to consider as "approaching"
     * @return bool True if switch is within specified days
     */
    public function isAutoSwitchApproaching(int $days = 30): bool
    {
        $daysUntil = $this->getDaysUntilAutoSwitch();

        if ($daysUntil === null) {
            return false;
        }

        return $daysUntil >= 0 && $daysUntil <= $days;
    }

    /**
     * Get all valid project version statuses
     *
     * @return array
     */
    public function getProjectVersions(): array
    {
        return $this->projectVersions;
    }

    /**
     * Get response structure documentation
     */
    public function getResponseStructure(): array
    {
        return [
            'projectsV2Status' => [
                'description' => 'Projects version status for the account',
                'fields' => [
                    'data.status' => 'Current Projects version (projects-v2 or legacy)',
                    'data.will_be_automatically_switched_on' => 'Date when account will be switched to projects-v2 (optional, format: YYYY-MM-DD)',
                ],
                'notes' => [
                    'The status field indicates which version of Projects the account is currently using',
                    'If will_be_automatically_switched_on is present, the account is scheduled for automatic migration',
                    'The auto-switch date is only present for legacy accounts that will be migrated',
                ],
            ],
        ];
    }

    /**
     * Override list method as it's not supported for accounts
     */
    public function list(array $filters = [], array $options = []): array
    {
        throw new InvalidArgumentException(
            'The accounts resource does not support list operations. Use projectsV2Status() method instead.'
        );
    }

    /**
     * Override info method as it's not supported for accounts
     */
    public function info($id, $includes = null): array
    {
        throw new InvalidArgumentException(
            'The accounts resource does not support info operations. Use projectsV2Status() method instead.'
        );
    }
}
