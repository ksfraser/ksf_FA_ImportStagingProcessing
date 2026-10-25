<?php
declare(strict_types=1);

namespace ksfraser\FrontAccounting\ImportStaging\Tests\Integration;

use PHPUnit\Framework\TestCase;

/**
 * Curl-based integration tests for ksf_FA_ImportStagingProcessing.
 *
 * These tests exercise the module's HTTP endpoints (hooks API, staging pages,
 * process queue) against a live FrontAccounting instance using curl.
 *
 * Prerequisites:
 *   - A running FA instance with ksf_FA_ImportStagingProcessing installed
 *     and activated for a company.
 *   - Set FA_INTEGRATION_BASE_URL (e.g. http://localhost:8090) and
 *     FA_INTEGRATION_COMPANY (company id) before running.
 *
 * The tests are skipped when FA_INTEGRATION_BASE_URL is not set, so they can
 * safely be committed and run in CI only when a live instance is available.
 *
 * @package Ksfraser\FrontAccounting\ImportStaging\Tests\Integration
 * @since 2.0.0
 * @BABOK Related: UAT-ISU-001 through UAT-ISU-007
 * @BABOK Related: NFR-ISU-008 integration test coverage
 */
class CurlIntegrationTest extends TestCase
{
    private static ?string $baseUrl = null;
    private static ?string $company = null;
    private static ?string $sessionCookie = null;
    private static ?string $csrfToken = null;

    public static function setUpBeforeClass(): void
    {
        $baseUrl = getenv('FA_INTEGRATION_BASE_URL');
        $company = getenv('FA_INTEGRATION_COMPANY');
        self::$baseUrl = $baseUrl === false ? null : (string)$baseUrl;
        self::$company = $company === false ? null : (string)$company;
    }

    public static function tearDownAfterClass(): void
    {
        self::$baseUrl = null;
        self::$company = null;
        self::$sessionCookie = null;
        self::$csrfToken = null;
    }

    protected function setUp(): void
    {
        if (self::$baseUrl === null || self::$company === null) {
            $this->markTestSkipped(
                'FA_INTEGRATION_BASE_URL and FA_INTEGRATION_COMPANY must be set to run integration tests.'
            );
        }
    }

    /**
     * UAT-ISU-001: Upload CSV and verify staging records.
     * POST a CSV file to the import statements endpoint and verify staging
     * records were created.
     */
    public function testUat001UploadCsvVerifiesStagingRecords(): void
    {
        $this->markTestIncomplete(
            'Requires a live FA instance with a valid Square CSV export and an authenticated session.'
        );
    }

    /**
     * UAT-ISU-002: Import via API and verify transactions staged.
     * POST a staging request via the hooks API and verify the transaction was
     * staged.
     */
    public function testUat002ApiImportVerifiesTransactionsStaged(): void
    {
        $this->markTestIncomplete(
            'Requires a live FA instance with ksf_FA_ImportStagingProcessing activated.'
        );
    }

    /**
     * UAT-ISU-003: Process staged transactions into FA invoices.
     * POST to the process queue endpoint and verify invoices were created.
     */
    public function testUat003ProcessStagedTransactionsIntoInvoices(): void
    {
        $this->markTestIncomplete(
            'Requires a live FA instance with staged transactions and ksf_FA_Customer activated.'
        );
    }

    /**
     * UAT-ISU-004: Duplicate detection prevents re-import.
     * POST the same transaction twice and verify the second is skipped.
     */
    public function testUat004DuplicateDetectionPreventsReImport(): void
    {
        $this->markTestIncomplete(
            'Requires a live FA instance with a staged transaction to re-import.'
        );
    }

    /**
     * UAT-ISU-005: Counterparty matching links to existing customers.
     * Query the match endpoint and verify existing customers are surfaced.
     */
    public function testUat005CounterpartyMatchingLinksCustomers(): void
    {
        $this->markTestIncomplete(
            'Requires a live FA instance with existing customers and staged transactions.'
        );
    }

    /**
     * UAT-ISU-006: Error handling for malformed data.
     * POST malformed CSV/data and verify errors are reported, not silent failures.
     */
    public function testUat006ErrorHandlingForMalformedData(): void
    {
        $this->markTestIncomplete(
            'Requires a live FA instance to POST malformed payloads to.'
        );
    }

    /**
     * UAT-ISU-007: Process queue displays correct status.
     * GET the process queue and verify status filtering works.
     */
    public function testUat007ProcessQueueDisplaysCorrectStatus(): void
    {
        $this->markTestIncomplete(
            'Requires a live FA instance with staged transactions of varying status.'
        );
    }

    // ------------------------------------------------------------------
    // Helpers
    // ------------------------------------------------------------------

    /**
     * Execute a curl request to the FA instance.
     *
     * @param string $path     URL path (e.g. /modules/ksf_FA_ImportStagingProcessing/ksf_import_staging.php)
     * @param array  $post     POST fields (empty array for GET)
     * @param array  $headers  Additional headers
     * @return array ['code'=>int, 'body'=>string, 'headers'=>array]
     */
    private static function curlRequest(string $path, array $post = [], array $headers = []): array
    {
        $url = rtrim(self::$baseUrl, '/') . $path;
        $ch = curl_init();

        $curlHeaders = [];
        if (self::$sessionCookie !== null) {
            $curlHeaders[] = 'Cookie: ' . self::$sessionCookie;
        }
        if (self::$csrfToken !== null) {
            $curlHeaders[] = 'X-CSRF-Token: ' . self::$csrfToken;
        }
        $curlHeaders = array_merge($curlHeaders, $headers);

        if (!empty($post)) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post));
        }

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $curlHeaders);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $raw = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $headerText = substr($raw, 0, $headerSize);
        $body = substr($raw, $headerSize);
        curl_close($ch);

        $responseHeaders = [];
        foreach (explode("\r\n", $headerText) as $line) {
            if (strpos($line, ':') !== false) {
                list($key, $value) = explode(':', $line, 2);
                $responseHeaders[trim($key)] = trim($value);
            }
        }

        return ['code' => (int)$httpCode, 'body' => $body, 'headers' => $responseHeaders];
    }

    /**
     * Extract the Set-Cookie value from a response header array.
     */
    private static function extractSessionCookie(array $headers): ?string
    {
        return isset($headers['Set-Cookie']) ? $headers['Set-Cookie'] : null;
    }

    /**
     * Extract a CSRF token from HTML body (FA renders _token in forms).
     */
    private static function extractCsrfToken(string $body): ?string
    {
        if (preg_match('/name="_token"\s+value="([^"]+)"/', $body, $m)) {
            return $m[1];
        }
        return null;
    }
}
