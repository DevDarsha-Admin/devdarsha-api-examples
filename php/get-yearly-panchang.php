<?php
/**
 * DevDarsha Panchang API — yearly endpoint (PHP example).
 *
 * Two ways to call it:
 *   1. devdarsha_get_yearly_curl()  — plain PHP using the cURL extension.
 *   2. devdarsha_get_yearly_wp()    — WordPress using wp_remote_post().
 *
 * Run the plain-PHP example from the CLI:
 *   export DEVDARSHA_API_KEY=your_api_key_here
 *   php php/get-yearly-panchang.php
 *
 * The yearly endpoint needs a Sapphire plan or higher. It fans out to all 12
 * months (X-Quota-Cost: 12) and is metered by the coverage meter — see README.
 *
 * Get a free API key at https://platform.devdarsha.com
 */

const DEVDARSHA_YEARLY_URL = 'https://panchang.devdarsha.com/v1/panchang/yearly';

/**
 * Plain PHP (cURL). $year is 1900–2100. Returns the decoded response envelope:
 *   ['data' => ['year' => ..., 'months' => [...]], 'meta' => [...], ...]
 *
 * @throws RuntimeException on transport or API error.
 */
function devdarsha_get_yearly_curl(string $apiKey, int $year, string $cityId): array
{
    $payload = json_encode([
        'year'    => $year,   // integer 1900–2100
        'city_id' => $cityId, // city_id works on all plans; lat/lon needs Amethyst+
    ]);

    $ch = curl_init(DEVDARSHA_YEARLY_URL);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => [
            'X-Api-Key: ' . $apiKey, // key in header, not in the body
            'Content-Type: application/json',
        ],
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_TIMEOUT        => 60, // a full year fans out to 12 months
    ]);

    $body   = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $errno  = curl_errno($ch);
    $error  = curl_error($ch);
    curl_close($ch);

    if ($errno) {
        throw new RuntimeException("cURL error: {$error}");
    }

    $result = json_decode($body, true);

    if ($status >= 400) {
        // Errors carry error, message, docs_url.
        throw new RuntimeException(
            "HTTP {$status} — {$result['error']}: {$result['message']} (see {$result['docs_url']})"
        );
    }

    return $result;
}

/**
 * WordPress flavour using wp_remote_post(). Use this inside a plugin or theme.
 *
 * @return array|WP_Error  Decoded envelope, or WP_Error on failure.
 */
function devdarsha_get_yearly_wp(string $apiKey, int $year, string $cityId)
{
    $response = wp_remote_post(DEVDARSHA_YEARLY_URL, [
        'headers' => [
            'X-Api-Key'    => $apiKey, // key in header, not the body
            'Content-Type' => 'application/json',
        ],
        'body'    => wp_json_encode([
            'year'    => $year,
            'city_id' => $cityId,
        ]),
        'timeout' => 60,
    ]);

    if (is_wp_error($response)) {
        return $response; // transport error
    }

    $status = wp_remote_retrieve_response_code($response);
    $result = json_decode(wp_remote_retrieve_body($response), true);

    if ($status >= 400) {
        return new WP_Error(
            $result['error'] ?? 'api_error',
            $result['message'] ?? 'Unknown error',
            ['status' => $status, 'docs_url' => $result['docs_url'] ?? null]
        );
    }

    return $result;
}

// ── CLI demo (only runs when executed directly, not when included) ───────────
if (PHP_SAPI === 'cli' && isset($argv) && realpath($argv[0]) === __FILE__) {
    $apiKey = getenv('DEVDARSHA_API_KEY');
    $cityId = getenv('DEVDARSHA_CITY_ID') ?: 'ujjain';
    $year   = (int) (getenv('DEVDARSHA_YEAR') ?: '2026');

    if (!$apiKey) {
        fwrite(STDERR, "Missing DEVDARSHA_API_KEY. Get a free key at https://platform.devdarsha.com\n");
        exit(1);
    }

    try {
        $result = devdarsha_get_yearly_curl($apiKey, $year, $cityId);
        $data   = $result['data'];

        printf("Yearly Panchang for %s — %d (%d months)\n", $cityId, $data['year'], count($data['months']));
        foreach ($data['months'] as $m) {
            // A partial month carries `error` instead of day data.
            $summary = isset($m['error'])
                ? "({$m['error']})"
                : (($m['total_days'] ?? count($m['days'] ?? [])) . ' days');
            printf("  %02d %s: %s\n", $m['month'], $m['month_name'], $summary);
        }
    } catch (RuntimeException $e) {
        fwrite(STDERR, $e->getMessage() . "\n");
        exit(1);
    }
}
