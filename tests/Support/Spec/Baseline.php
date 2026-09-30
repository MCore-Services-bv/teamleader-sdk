<?php

declare(strict_types=1);

namespace McoreServices\TeamleaderSDK\Tests\Support\Spec;

use RuntimeException;

/**
 * tests/Fixtures/specification/baseline.json — the audit's to-do list.
 *
 *   open      Known divergences not yet fixed. Each category pass removes its
 *             entries as it fixes them; the file shrinks towards empty.
 *   accepted  Deliberate divergences, each with the reason it is deliberate —
 *             for example a filter the API honours but does not declare,
 *             verified against a live account.
 *
 * SpecParityTest fails on a finding in neither list (a regression, or a
 * specification change) and on an entry in either list that is no longer
 * found (fixed, so remove it). Both directions matter: a stale entry would
 * silently allow the defect back.
 */
final class Baseline
{
    /** @var array<string, string> */
    private array $open = [];

    /** @var array<string, string> */
    private array $accepted = [];

    private ?string $version = null;

    public function __construct(private readonly string $path)
    {
        if (! is_file($path)) {
            return;
        }

        $data = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);

        $this->version = $data['specification_version'] ?? null;
        $this->open = $data['open'] ?? [];
        $this->accepted = $data['accepted'] ?? [];

        foreach ($this->accepted as $id => $reason) {
            if (! is_string($reason) || trim($reason) === '') {
                throw new RuntimeException("Accepted baseline entry {$id} has no reason. Every accepted divergence must say why.");
            }
        }
    }

    public static function defaultPath(): string
    {
        return dirname(__DIR__, 2).'/Fixtures/specification/baseline.json';
    }

    public function version(): ?string
    {
        return $this->version;
    }

    public function isKnown(string $id): bool
    {
        return isset($this->open[$id]) || isset($this->accepted[$id]);
    }

    public function isAccepted(string $id): bool
    {
        return isset($this->accepted[$id]);
    }

    /**
     * Findings not covered by the baseline.
     *
     * @param  array<string, array<string, mixed>>  $findings
     * @return array<string, array<string, mixed>>
     */
    public function unexpected(array $findings): array
    {
        return array_filter($findings, fn (array $finding) => ! $this->isKnown($finding['id']));
    }

    /**
     * Baseline entries no longer produced by the auditor.
     *
     * @param  array<string, array<string, mixed>>  $findings
     * @return array<string, string> id => 'open'|'accepted'
     */
    public function stale(array $findings): array
    {
        $stale = [];

        foreach (array_keys($this->open) as $id) {
            if (! isset($findings[$id])) {
                $stale[$id] = 'open';
            }
        }

        foreach (array_keys($this->accepted) as $id) {
            if (! isset($findings[$id])) {
                $stale[$id] = 'accepted';
            }
        }

        ksort($stale);

        return $stale;
    }

    /**
     * Rewrite the baseline from the current findings, keeping accepted
     * entries (and their reasons) that are still produced.
     *
     * Info-level findings are not gated and never written.
     *
     * @param  array<string, array<string, mixed>>  $findings
     */
    public function write(array $findings, string $version): void
    {
        $open = [];
        $accepted = [];

        foreach ($findings as $id => $finding) {
            if ($finding['severity'] === 'info') {
                continue;
            }

            if (isset($this->accepted[$id])) {
                $accepted[$id] = $this->accepted[$id];
            } else {
                $open[$id] = $finding['message'];
            }
        }

        ksort($open);
        ksort($accepted);

        $payload = [
            '_comment' => 'Known divergences from the specification. Remove an entry when you fix it; move it to "accepted" with a reason when it is deliberate. Regenerate with: php bin/spec-audit --write-baseline',
            'specification_version' => $version,
            'open' => (object) $open,
            'accepted' => (object) $accepted,
        ];

        file_put_contents(
            $this->path,
            json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)."\n"
        );

        $this->open = $open;
        $this->accepted = $accepted;
        $this->version = $version;
    }
}
