<?php

declare(strict_types=1);

namespace McoreServices\TeamleaderSDK\Traits;

use Illuminate\Support\Facades\Storage;
use McoreServices\TeamleaderSDK\Exceptions\TeamleaderException;
use RuntimeException;

/**
 * downloadContents() and downloadTo() for a resource with a download()
 * endpoint: request the temporary link, then fetch it at once, before it
 * expires.
 *
 * download() itself keeps returning the link (`data.location`, `data.expires`).
 */
trait DownloadsDocuments
{
    /**
     * The document's bytes, in one call.
     *
     *     $pdf = Teamleader::invoices()->downloadContents($invoiceId);
     *
     * @param  string  $id  The record's UUID
     * @param  string  $format  The format download() accepts, e.g. pdf
     *
     * @throws TeamleaderException When no link comes back, or the download is refused
     */
    public function downloadContents(string $id, string $format = 'pdf'): string
    {
        return $this->api->fetchFileContents($this->downloadLocation($id, $format));
    }

    /**
     * Download the document onto a Laravel disk, and return the path.
     *
     *     Teamleader::invoices()->downloadTo($invoiceId, 's3', "invoices/{$number}.pdf");
     *
     * Bulk-able: `bulk()->call('invoices', 'downloadTo', [[$id, 's3', $path], …])`.
     * On a dry run nothing is written.
     *
     * @throws TeamleaderException When no link comes back, or the download is refused
     * @throws RuntimeException When the disk refuses the file
     */
    public function downloadTo(string $id, string $disk, string $path, string $format = 'pdf'): string
    {
        $contents = $this->downloadContents($id, $format);

        if ($this->api->isDryRun()) {
            return $path;
        }

        if (! Storage::disk($disk)->put($path, $contents)) {
            throw new RuntimeException("The '{$disk}' disk refused to store {$path}.");
        }

        return $path;
    }

    /**
     * @throws TeamleaderException
     */
    private function downloadLocation(string $id, string $format): string
    {
        // Files::download() takes no format; PHP ignores the extra argument
        $response = $this->download($id, $format);
        $location = $response['data']['location'] ?? null;

        if (! is_string($location) || $location === '') {
            throw new TeamleaderException(
                $this->getBasePath().'.download returned no link'
                .(isset($response['message']) ? ': '.$response['message'] : '.')
            );
        }

        return $location;
    }
}
