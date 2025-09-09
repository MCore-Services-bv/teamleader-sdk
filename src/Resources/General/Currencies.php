<?php

namespace McoreServices\TeamleaderSDK\Resources\General;

use BadMethodCallException;
use InvalidArgumentException;
use McoreServices\TeamleaderSDK\Resources\Resource;

class Currencies extends Resource
{
    protected string $description = 'Manage currency exchange rates in Teamleader Focus';

    // Resource capabilities - very limited for currencies
    protected bool $supportsPagination = false;
    protected bool $supportsFiltering = false;
    protected bool $supportsSorting = false;
    protected bool $supportsSideloading = false;
    protected bool $supportsCreation = false;
    protected bool $supportsUpdate = false;
    protected bool $supportsDeletion = false;
    protected bool $supportsBatch = false;

    // Available includes for sideloading (none for currencies)
    protected array $availableIncludes = [];

    // Common filters (none for exchange rates)
    protected array $commonFilters = [];

    // Usage examples specific to currencies
    protected array $usageExamples = [
        'get_exchange_rates_eur' => [
            'description' => 'Get exchange rates for EUR as base currency',
            'code' => '$rates = $teamleader->currencies()->exchangeRates(\'EUR\');'
        ],
        'get_exchange_rates_usd' => [
            'description' => 'Get exchange rates for USD as base currency',
            'code' => '$rates = $teamleader->currencies()->exchangeRates(\'USD\');'
        ],
        'get_specific_rate' => [
            'description' => 'Get specific currency rate from response',
            'code' => '$rates = $teamleader->currencies()->exchangeRates(\'EUR\');' . "\n" .
                '$usdRate = collect($rates[\'data\'])->firstWhere(\'code\', \'USD\')[\'exchange_rate\'] ?? null;'
        ]
    ];

    /**
     * Get exchange rates for EUR (convenience method)
     *
     * @return array
     */
    public function eurRates(): array
    {
        return $this->exchangeRates('EUR');
    }

    /**
     * Get exchange rates for a specific base currency
     *
     * @param string $baseCurrency The base currency code (e.g., 'EUR', 'USD')
     * @return array
     */
    public function exchangeRates(string $baseCurrency): array
    {
        // Validate currency code
        if (!$this->isValidCurrencyCode($baseCurrency)) {
            throw new InvalidArgumentException("Invalid currency code: {$baseCurrency}");
        }

        return $this->api->request('POST', $this->getBasePath() . '.exchangeRates', [
            'base' => strtoupper($baseCurrency)
        ]);
    }

    /**
     * Check if a currency code is supported
     *
     * @param string $currencyCode
     * @return bool
     */
    public function isValidCurrencyCode(string $currencyCode): bool
    {
        return in_array(strtoupper($currencyCode), $this->getSupportedCurrencyCodes());
    }

    /**
     * Get supported currency codes only
     *
     * @return array
     */
    public function getSupportedCurrencyCodes(): array
    {
        return array_keys($this->getSupportedCurrencies());
    }

    /**
     * Get all supported currency codes
     *
     * @return array
     */
    public function getSupportedCurrencies(): array
    {
        return [
            'BAM' => 'Bosnian Mark',
            'CAD' => 'Canadian Dollar',
            'CHF' => 'Swiss Franc',
            'CLP' => 'Chilean Peso',
            'CNY' => 'Chinese Yuan',
            'COP' => 'Colombian Peso',
            'CZK' => 'Czech Koruna',
            'DKK' => 'Danish Krone',
            'EUR' => 'Euro',
            'GBP' => 'British Pound',
            'INR' => 'Indian Rupee',
            'ISK' => 'Icelandic Krona',
            'JPY' => 'Japanese Yen',
            'MAD' => 'Moroccan Dirham',
            'MXN' => 'Mexican Peso',
            'NOK' => 'Norwegian Krone',
            'PEN' => 'Peruvian Sol',
            'PLN' => 'Polish Zloty',
            'RON' => 'Romanian Leu',
            'SEK' => 'Swedish Krona',
            'TRY' => 'Turkish Lira',
            'USD' => 'US Dollar',
            'ZAR' => 'South African Rand'
        ];
    }

    /**
     * Get the base path for the currencies resource
     */
    protected function getBasePath(): string
    {
        return 'currencies';
    }

    /**
     * Get exchange rates for USD (convenience method)
     *
     * @return array
     */
    public function usdRates(): array
    {
        return $this->exchangeRates('USD');
    }

    /**
     * Get exchange rates for GBP (convenience method)
     *
     * @return array
     */
    public function gbpRates(): array
    {
        return $this->exchangeRates('GBP');
    }

    /**
     * Convert amount from one currency to another using current exchange rates
     *
     * @param float $amount
     * @param string $fromCurrency
     * @param string $toCurrency
     * @return array Contains converted amount and rate used
     */
    public function convert(float $amount, string $fromCurrency, string $toCurrency): array
    {
        if (strtoupper($fromCurrency) === strtoupper($toCurrency)) {
            return [
                'amount' => $amount,
                'converted_amount' => $amount,
                'exchange_rate' => 1.0,
                'from_currency' => strtoupper($fromCurrency),
                'to_currency' => strtoupper($toCurrency)
            ];
        }

        // Get exchange rates using fromCurrency as base
        $rates = $this->exchangeRates($fromCurrency);

        if (isset($rates['error']) && $rates['error']) {
            return $rates; // Return error response
        }

        // Find the target currency rate
        $targetRate = null;
        foreach ($rates['data'] ?? [] as $currency) {
            if ($currency['code'] === strtoupper($toCurrency)) {
                $targetRate = $currency['exchange_rate'];
                break;
            }
        }

        if ($targetRate === null) {
            return [
                'error' => true,
                'message' => "Exchange rate not found for {$fromCurrency} to {$toCurrency}"
            ];
        }

        $convertedAmount = $amount * $targetRate;

        return [
            'amount' => $amount,
            'converted_amount' => round($convertedAmount, 4),
            'exchange_rate' => $targetRate,
            'from_currency' => strtoupper($fromCurrency),
            'to_currency' => strtoupper($toCurrency)
        ];
    }

    /**
     * Override the list method to prevent confusion (not applicable for currencies)
     */
    public function list(array $filters = [], array $options = [])
    {
        throw new BadMethodCallException(
            'The list() method is not available for currencies. Use exchangeRates($baseCurrency) instead.'
        );
    }

    /**
     * Override the info method to prevent confusion (not applicable for currencies)
     */
    public function info($id, $includes = null)
    {
        throw new BadMethodCallException(
            'The info() method is not available for currencies. Use exchangeRates($baseCurrency) instead.'
        );
    }

    /**
     * Get common currency pairs for quick access
     *
     * @return array
     */
    public function getCommonPairs(): array
    {
        return [
            'EUR/USD' => ['base' => 'EUR', 'target' => 'USD'],
            'USD/EUR' => ['base' => 'USD', 'target' => 'EUR'],
            'GBP/EUR' => ['base' => 'GBP', 'target' => 'EUR'],
            'EUR/GBP' => ['base' => 'EUR', 'target' => 'GBP'],
            'USD/GBP' => ['base' => 'USD', 'target' => 'GBP'],
            'GBP/USD' => ['base' => 'GBP', 'target' => 'USD'],
            'EUR/CHF' => ['base' => 'EUR', 'target' => 'CHF'],
            'USD/JPY' => ['base' => 'USD', 'target' => 'JPY']
        ];
    }

    /**
     * Get rate for a specific currency pair
     *
     * @param string $baseCurrency
     * @param string $targetCurrency
     * @return array
     */
    public function getRate(string $baseCurrency, string $targetCurrency): array
    {
        if (strtoupper($baseCurrency) === strtoupper($targetCurrency)) {
            return [
                'base' => strtoupper($baseCurrency),
                'target' => strtoupper($targetCurrency),
                'rate' => 1.0,
                'symbol' => $this->getCurrencySymbol($targetCurrency),
                'name' => $this->getCurrencyName($targetCurrency)
            ];
        }

        $rates = $this->exchangeRates($baseCurrency);

        if (isset($rates['error']) && $rates['error']) {
            return $rates;
        }

        foreach ($rates['data'] ?? [] as $currency) {
            if ($currency['code'] === strtoupper($targetCurrency)) {
                return [
                    'base' => strtoupper($baseCurrency),
                    'target' => strtoupper($targetCurrency),
                    'rate' => $currency['exchange_rate'],
                    'symbol' => $currency['symbol'],
                    'name' => $currency['name']
                ];
            }
        }

        return [
            'error' => true,
            'message' => "Exchange rate not found for {$baseCurrency}/{$targetCurrency}"
        ];
    }

    /**
     * Get currency symbol (basic mapping)
     *
     * @param string $currencyCode
     * @return string
     */
    private function getCurrencySymbol(string $currencyCode): string
    {
        $symbols = [
            'EUR' => '€',
            'USD' => '$',
            'GBP' => '£',
            'JPY' => '¥',
            'CHF' => 'CHF',
            'CAD' => 'C$',
            'AUD' => 'A$'
        ];

        return $symbols[strtoupper($currencyCode)] ?? strtoupper($currencyCode);
    }

    /**
     * Get currency name by code
     *
     * @param string $currencyCode
     * @return string|null
     */
    public function getCurrencyName(string $currencyCode): ?string
    {
        $currencies = $this->getSupportedCurrencies();
        return $currencies[strtoupper($currencyCode)] ?? null;
    }

    /**
     * Override getSuggestedIncludes as currencies don't have includes
     *
     * @return array
     */
    protected function getSuggestedIncludes(): array
    {
        return []; // Currencies don't have sideloadable relationships
    }
}
