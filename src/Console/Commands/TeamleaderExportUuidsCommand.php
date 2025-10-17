<?php

namespace McoreServices\TeamleaderSDK\Console\Commands;

use Illuminate\Console\Command;
use McoreServices\TeamleaderSDK\Facades\Teamleader;

class TeamleaderExportUuidsCommand extends Command
{
    protected $signature = 'teamleader:export-uuids {--resource=all}';

    protected $description = 'Export Teamleader UUIDs for config file';

    public function handle()
    {
        $resource = $this->option('resource');

        $this->info('Fetching UUIDs from Teamleader...');
        $this->newLine();

        try {
            if ($resource === 'all' || $resource === 'departments') {
                $this->exportDepartments();
            }

            if ($resource === 'all' || $resource === 'users') {
                $this->exportUsers();
            }

            if ($resource === 'all' || $resource === 'teams') {
                $this->exportTeams();
            }

            if ($resource === 'all' || $resource === 'work-types') {
                $this->exportWorkTypes();
            }

            if ($resource === 'all' || $resource === 'pipelines') {
                $this->exportPipelines();
            }

            if ($resource === 'all' || $resource === 'deal-phases') {
                $this->exportDealPhases();
            }

            if ($resource === 'all' || $resource === 'deal-sources') {
                $this->exportDealSources();
            }

            if ($resource === 'all' || $resource === 'lost-reasons') {
                $this->exportLostReasons();
            }

            if ($resource === 'all' || $resource === 'payment-terms') {
                $this->exportPaymentTerms();
            }

            if ($resource === 'all' || $resource === 'tax-rates') {
                $this->exportTaxRates();
            }

            if ($resource === 'all' || $resource === 'price-lists') {
                $this->exportPriceLists();
            }

            if ($resource === 'all' || $resource === 'business-types') {
                $this->exportBusinessTypes();
            }

            if ($resource === 'all' || $resource === 'product-categories') {
                $this->exportProductCategories();
            }

            if ($resource === 'all' || $resource === 'activity-types') {
                $this->exportActivityTypes();
            }

            if ($resource === 'all' || $resource === 'call-outcomes') {
                $this->exportCallOutcomes();
            }

            if ($resource === 'all' || $resource === 'units-of-measure') {
                $this->exportUnitsOfMeasure();
            }

            if ($resource === 'all' || $resource === 'custom-fields') {
                $this->exportCustomFields();
            }

            $this->newLine();
            $this->info('✓ Done! Copy the output above into your config/teamleader.php file.');

        } catch (\Exception $e) {
            $this->error('Error: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }

    protected function exportDepartments()
    {
        $this->info('=== Departments ===');
        $response = Teamleader::departments()->list();
        $this->exportResource($response, 'name');
        $this->newLine();
    }

    protected function exportUsers()
    {
        $this->info('=== Users ===');
        $response = Teamleader::users()->list();
        foreach ($response['data'] as $item) {
            $name = $this->formatName($item['first_name'] . ' ' . $item['last_name']);
            $this->line("'{$name}' => '{$item['id']}',");
        }
        $this->newLine();
    }

    protected function exportTeams()
    {
        $this->info('=== Teams ===');
        $response = Teamleader::teams()->list();
        $this->exportResource($response, 'name');
        $this->newLine();
    }

    protected function exportWorkTypes()
    {
        $this->info('=== Work Types ===');
        $response = Teamleader::workTypes()->list();
        $this->exportResource($response, 'name');
        $this->newLine();
    }

    protected function exportPipelines()
    {
        $this->info('=== Deal Pipelines ===');
        $response = Teamleader::dealPipelines()->list();
        $this->exportResource($response, 'name');
        $this->newLine();
    }

    protected function exportDealPhases()
    {
        $this->info('=== Deal Phases ===');
        $pipelines = Teamleader::dealPipelines()->list();

        foreach ($pipelines['data'] as $pipeline) {
            $this->comment("// {$pipeline['name']} Pipeline");
            $phases = Teamleader::dealPhases()->forPipeline($pipeline['id']);
            $this->exportResource($phases, 'name');
        }
        $this->newLine();
    }

    protected function exportDealSources()
    {
        $this->info('=== Deal Sources ===');
        $response = Teamleader::dealSources()->list();
        $this->exportResource($response, 'name');
        $this->newLine();
    }

    protected function exportLostReasons()
    {
        $this->info('=== Lost Reasons ===');
        $response = Teamleader::lostReasons()->list();
        $this->exportResource($response, 'name');
        $this->newLine();
    }

    protected function exportPaymentTerms()
    {
        $this->info('=== Payment Terms ===');
        $response = Teamleader::paymentTerms()->list();

        foreach ($response['data'] as $item) {
            $name = $this->formatName($item['description']);
            $comment = $item['type'] === 'after_invoice_date' ? " // {$item['days']} days" : '';
            $this->line("'{$name}' => '{$item['id']}',{$comment}");
        }
        $this->newLine();
    }

    protected function exportTaxRates()
    {
        $this->info('=== Tax Rates ===');
        $response = Teamleader::taxRates()->list();

        foreach ($response['data'] as $item) {
            $name = 'vat_' . str_replace('.', '_', $item['rate']);
            $this->line("'{$name}' => '{$item['id']}', // {$item['rate']}%");
        }
        $this->newLine();
    }

    protected function exportPriceLists()
    {
        $this->info('=== Price Lists ===');
        $response = Teamleader::priceLists()->list();
        $this->exportResource($response, 'name');
        $this->newLine();
    }

    protected function exportBusinessTypes()
    {
        $this->info('=== Business Types ===');
        $this->comment('// Specify country code: --country=BE');

        $country = $this->option('country') ?? 'BE';
        $response = Teamleader::businessTypes()->forCountry($country);

        $this->comment("// {$country} Business Types");
        $this->exportResource($response, 'name');
        $this->newLine();
    }

    protected function exportProductCategories()
    {
        $this->info('=== Product Categories ===');
        $response = Teamleader::productCategories()->list();
        $this->exportResource($response, 'name');
        $this->newLine();
    }

    protected function exportActivityTypes()
    {
        $this->info('=== Activity Types ===');
        $response = Teamleader::activityTypes()->list();
        $this->exportResource($response, 'name');
        $this->newLine();
    }

    protected function exportCallOutcomes()
    {
        $this->info('=== Call Outcomes ===');
        $response = Teamleader::callOutcomes()->list();
        $this->exportResource($response, 'name');
        $this->newLine();
    }

    protected function exportUnitsOfMeasure()
    {
        $this->info('=== Units of Measure ===');
        $response = Teamleader::unitsOfMeasure()->list();
        $this->exportResource($response, 'name');
        $this->newLine();
    }

    protected function exportCustomFields()
    {
        $this->info('=== Custom Fields ===');
        $response = Teamleader::customFields()->list();

        // Group by context
        $contexts = [];
        foreach ($response['data'] as $field) {
            $context = $field['context'] ?? 'unknown';
            if (!isset($contexts[$context])) {
                $contexts[$context] = [];
            }
            $contexts[$context][] = $field;
        }

        foreach ($contexts as $context => $fields) {
            $this->comment("// {$context} Custom Fields");
            foreach ($fields as $field) {
                $name = $this->formatName($field['label']);
                $this->line("'{$name}' => '{$field['id']}',");
            }
            $this->newLine();
        }
    }

    protected function exportResource($response, $nameField)
    {
        foreach ($response['data'] as $item) {
            $name = $this->formatName($item[$nameField]);
            $this->line("'{$name}' => '{$item['id']}',");
        }
    }

    protected function formatName($name)
    {
        return strtolower(str_replace([' ', '-', '/'], '_', $name));
    }
}
