<?php
/**
 * DevDarsha Panchang API — monthly endpoint (PHP example).
 *
 * Two ways to call it:
 *   1. devdarsha_get_monthly_curl()  — plain PHP using the cURL extension.
 *   2. devdarsha_get_monthly_wp()    — WordPress using wp_remote_post().
 *
 * Run the plain-PHP example from the CLI:
 *   export DEVDARSHA_API_KEY=your_api_key_here
 *   php php/get-monthly-panchang.php
 *
 * The monthly endpoint needs an Amethyst plan or higher and is metered by the
 * coverage meter (distinct location-date pairs per account) — see the README.
 *
 * Get a free API key at https://platform.devdarsha.com
 */

const DEVDARSHA_MONTHLY_URL = 'https://panchang.devdarsha.com/v1/panchang/monthly';

/**
 * Plain PHP (cURL). $month is "YYYY-MM". Returns the decoded response envelope:
 *   ['data' => ['month' => ..., 'days' => [...]], 'meta' => [...], ...]
 *
 * @throws RuntimeException on transport or API error.
 */
function devdarsha_get_monthly_curl(string $apiKey, string $month, string $cityId): array
{
    $payload = json_encode([
        'date'    => $month,  // "YYYY-MM" (or send 'year' + 'month' integers)
        'city_id' => $cityId, // city_id works on all plans; lat/lon needs Amethyst+
    ]);

    $ch = curl_init(DEVDARSHA_MONTHLY_URL);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => [
            'X-Api-Key: ' . $apiKey, // key in header, not in the body
            'Content-Type: application/json',
        ],
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_TIMEOUT        => 30,
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
function devdarsha_get_monthly_wp(string $apiKey, string $month, string $cityId)
{
    $response = wp_remote_post(DEVDARSHA_MONTHLY_URL, [
        'headers' => [
            'X-Api-Key'    => $apiKey, // key in header, not the body
            'Content-Type' => 'application/json',
        ],
        'body'    => wp_json_encode([
            'date'    => $month,
            'city_id' => $cityId,
        ]),
        'timeout' => 30,
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
    $month  = getenv('DEVDARSHA_MONTH') ?: '2026-06';

    if (!$apiKey) {
        fwrite(STDERR, "Missing DEVDARSHA_API_KEY. Get a free key at https://platform.devdarsha.com\n");
        exit(1);
    }

    try {
        $result = devdarsha_get_monthly_curl($apiKey, $month, $cityId);
        $data   = $result['data'];

        printf("Monthly Panchang for %s — %s (%d days)\n", $data['city'], $data['month'], $data['total_days']);
        foreach ($data['days'] as $day) {
            printf("  %s (%s): %s\n", $day['date'], $day['weekday'], $day['tithi'][0]['name']);
        }
    } catch (RuntimeException $e) {
        fwrite(STDERR, $e->getMessage() . "\n");
        exit(1);
    }
}
