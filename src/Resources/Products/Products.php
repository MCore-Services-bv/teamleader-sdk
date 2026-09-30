<?php

namespace McoreServices\TeamleaderSDK\Resources\Products;

use InvalidArgumentException;
use McoreServices\TeamleaderSDK\Resources\Resource;
use McoreServices\TeamleaderSDK\Traits\ValidatesWritePayload;

class Products extends Resource
{
    use ValidatesWritePayload;

    /**
     * Body fields products.add accepts. Either `name` or `code` is required
     * (the spec's "Add Product by Name" / "Add Product by Code" variants).
     */
    public const ADD_FIELDS = [
        'name', 'code', 'description', 'purchase_price', 'selling_price', 'unit_of_measure_id',
        'price_list_prices', 'stock', 'configuration', 'department_id', 'product_category_id',
        'tax_rate_id', 'custom_fields',
    ];

    /** Body fields products.update accepts, besides `id` — the same set */
    public const UPDATE_FIELDS = self::ADD_FIELDS;

    /** Includes products.info accepts; products.list takes none */
    public const INFO_INCLUDES = ['suppliers'];

    /** `purchase_price.currency` and `selling_price.currency` */
    public const CURRENCIES = [
        'BAM', 'CAD', 'CHF', 'CLP', 'CNY', 'COP', 'CZK', 'DKK', 'EUR', 'GBP', 'INR', 'ISK',
        'JPY', 'MAD', 'MXN', 'NOK', 'PEN', 'PLN', 'RON', 'SEK', 'TRY', 'USD', 'ZAR',
    ];

    /** `configuration.stock_threshold.action` */
    public const STOCK_THRESHOLD_ACTIONS = ['notify'];

    protected string $description = 'Manage products in Teamleader Focus';

    protected bool $supportsCreation = true;

    protected bool $supportsUpdate = true;

    protected bool $supportsDeletion = true;

    protected bool $supportsBatch = false;

    protected bool $supportsPagination = true;

    protected bool $supportsSorting = false;

    protected bool $supportsFiltering = true;

    protected bool $supportsSideloading = true;

    /**
     * products.list takes no includes. `suppliers` is an info-only include —
     * see $infoIncludes.
     *
     * `suppliers` and `custom_fields` were listed here until v2.2.15.
     * `custom_fields` is not an include at all: products.info returns custom
     * fields on every call.
     */
    protected array $availableIncludes = [];

    protected array $infoIncludes = self::INFO_INCLUDES;

    protected array $defaultIncludes = [];

    protected array $commonFilters = [
        'ids' => 'Array of product UUIDs',
        'term' => 'Search term (will filter on the name or the code)',
        'updated_since' => 'ISO 8601 datetime',
    ];

    protected array $usageExamples = [
        'list_all' => [
            'description' => 'Get all products',
            'code' => '$products = $teamleader->products()->list();',
        ],
        'search_by_term' => [
            'description' => 'Search products by name or code',
            'code' => '$products = $teamleader->products()->search("cookies");',
        ],
        'with_suppliers' => [
            'description' => 'Get a product with its suppliers',
            'code' => '$product = $teamleader->products()->withSuppliers()->info("product-uuid");',
        ],
        'create_product' => [
            'description' => 'Create a new product',
            'code' => '$product = $teamleader->products()->create(["name" => "Dark Chocolate Cookies", "code" => "COOK-DARK-001"]);',
        ],
        'price_list_prices' => [
            'description' => 'Set a price on a price list',
            'code' => '$teamleader->products()->update("product-uuid", [
    "price_list_prices" => [
        ["price_list_id" => "price-list-uuid", "price" => ["amount" => 9.95, "currency" => "EUR"]],
    ],
]);',
        ],
    ];

    protected function getBasePath(): string
    {
        return 'products';
    }

    /**
     * List products
     *
     * @param  array  $filters  ids, term (name or code), updated_since
     * @param  array  $options  page_size, page_number
     *
     * @throws InvalidArgumentException On an unknown filter key or option
     */
    public function list(array $filters = [], array $options = []): array
    {
        $unknown = array_diff(array_keys($options), ['page_size', 'page_number', 'filters']);

        if ($unknown !== []) {
            throw new InvalidArgumentException(
                'products.list does not support: '.implode(', ', $unknown)
                .'. Supported: page_size, page_number. Includes (suppliers) go on info().'
            );
        }

        if ($this->getPendingIncludes() !== []) {
            $this->pendingIncludes = [];

            throw new InvalidArgumentException('products.list takes no includes; suppliers is available on info() only.');
        }

        $params = [];
        $filter = $this->buildFilters($filters);

        if ($filter !== []) {
            $params['filter'] = $filter;
        }

        $params['page'] = [
            'size' => (int) ($options['page_size'] ?? 20),
            'number' => (int) ($options['page_number'] ?? 1),
        ];

        return $this->api->request('POST', $this->getBasePath().'.list', $params);
    }

    /**
     * Get one product
     *
     * Custom fields are part of every info response; `suppliers` is the only
     * include.
     *
     * @param  string  $id  Product UUID
     * @param  mixed  $includes  suppliers
     *
     * @throws InvalidArgumentException On an include products.info does not accept
     */
    public function info($id, $includes = null): array
    {
        $pending = $this->getPendingIncludes();
        $this->pendingIncludes = [];

        $includes = $this->assertIncludes([...(array) ($includes ?? []), ...$pending], self::INFO_INCLUDES, 'products.info');

        return $this->api->request('POST', $this->getBasePath().'.info', $this->applyIncludes(['id' => $id], $includes));
    }

    /**
     * Create a product
     *
     * Requires a name or a code.
     *
     * @throws InvalidArgumentException When neither is given, or a field or value is not accepted
     */
    public function create(array $data): array
    {
        return $this->api->request('POST', $this->getBasePath().'.add', $this->validateProductData($data, 'create'));
    }

    /**
     * Update a product
     *
     * Every field is optional; name, code, description, prices,
     * unit_of_measure_id and configuration take null to clear them.
     *
     * @throws InvalidArgumentException When a field or value is not accepted
     */
    public function update($id, array $data): array
    {
        $data['id'] = $id;

        return $this->api->request('POST', $this->getBasePath().'.update', $this->validateProductData($data, 'update'));
    }

    public function delete($id, ...$additionalParams): array
    {
        return $this->api->request('POST', $this->getBasePath().'.delete', ['id' => $id]);
    }

    /**
     * Search products by name or code
     */
    public function search(string $term, array $options = []): array
    {
        return $this->list(
            array_merge(['term' => $term], $options['filters'] ?? []),
            $options
        );
    }

    /**
     * Get products updated since a datetime
     *
     * @param  string  $date  ISO 8601 datetime
     */
    public function updatedSince(string $date, array $options = []): array
    {
        return $this->list(
            array_merge(['updated_since' => $date], $options['filters'] ?? []),
            $options
        );
    }

    /**
     * Validate a create or update body against the specification
     *
     * Until v2.2.15 this built a table of rules and then checked none of them:
     * any field, currency or shape was sent as given.
     *
     * @throws InvalidArgumentException
     */
    protected function validateProductData(array $data, string $operation = 'create'): array
    {
        $endpoint = $operation === 'create' ? 'products.add' : 'products.update';

        if ($operation === 'create') {
            if (empty($data['name']) && empty($data['code'])) {
                throw new InvalidArgumentException('Either name or code is required when creating a product');
            }

            $this->rejectUnknownFields($data, self::ADD_FIELDS, $endpoint);
        } else {
            $this->rejectUnknownFields($data, [...self::UPDATE_FIELDS, 'id'], $endpoint);
        }

        foreach (['purchase_price', 'selling_price'] as $field) {
            if (isset($data[$field])) {
                $this->assertPrice($data[$field], $field, $endpoint);
            }
        }

        if (isset($data['price_list_prices'])) {
            if (! is_array($data['price_list_prices']) || ! array_is_list($data['price_list_prices'])) {
                throw new InvalidArgumentException("price_list_prices must be a list of ['price_list_id' => uuid, 'price' => ['amount' => ..., 'currency' => ...]]");
            }

            foreach ($data['price_list_prices'] as $index => $entry) {
                if (! is_array($entry) || empty($entry['price_list_id']) || ! isset($entry['price'])) {
                    throw new InvalidArgumentException("price_list_prices[{$index}] needs a price_list_id and a price");
                }

                $this->assertPrice($entry['price'], "price_list_prices[{$index}].price", $endpoint);
            }
        }

        if (isset($data['stock']) && (! is_array($data['stock']) || (isset($data['stock']['amount']) && ! is_numeric($data['stock']['amount'])))) {
            throw new InvalidArgumentException("stock must be ['amount' => number], or ['amount' => null]");
        }

        $threshold = $data['configuration']['stock_threshold'] ?? null;

        if ($threshold !== null) {
            if (! is_array($threshold) || ! isset($threshold['minimum'], $threshold['action'])) {
                throw new InvalidArgumentException("configuration.stock_threshold needs a minimum and an action ('notify')");
            }

            if (! is_numeric($threshold['minimum']) || $threshold['minimum'] < 0) {
                throw new InvalidArgumentException('configuration.stock_threshold.minimum cannot be negative');
            }

            $this->assertEnum($threshold['action'], self::STOCK_THRESHOLD_ACTIONS, 'configuration.stock_threshold.action', $endpoint);
        }

        return $data;
    }

    /**
     * @throws InvalidArgumentException
     */
    private function assertPrice(mixed $price, string $path, string $endpoint): void
    {
        if (! is_array($price) || ! isset($price['amount'], $price['currency']) || ! is_numeric($price['amount'])) {
            throw new InvalidArgumentException("{$path} must be ['amount' => number, 'currency' => code]");
        }

        $this->assertEnum($price['currency'], self::CURRENCIES, "{$path}.currency", $endpoint);
    }

    /**
     * Build the filter object for products.list
     *
     * Until v2.2.15 filters were passed through unchecked, so a mistyped key
     * returned every product. `search` and `general_search` are accepted as
     * aliases for `term`.
     *
     * @throws InvalidArgumentException On an unknown key
     */
    protected function buildFilters(array $filters): array
    {
        $this->rejectUnknownFilters($filters, 'products.list', ['search', 'general_search']);

        foreach (['search', 'general_search'] as $alias) {
            if (isset($filters[$alias])) {
                $filters['term'] ??= $filters[$alias];
                unset($filters[$alias]);
            }
        }

        if (isset($filters['ids']) && ! is_array($filters['ids'])) {
            $filters['ids'] = [$filters['ids']];
        }

        return array_filter($filters, fn ($value) => $value !== null && $value !== '');
    }

    public function getAvailableSortFields(): array
    {
        return [];
    }

    protected function getSuggestedIncludes(): array
    {
        return $this->defaultIncludes;
    }

    public function withSuppliers(): self
    {
        return $this->with('suppliers');
    }
}
