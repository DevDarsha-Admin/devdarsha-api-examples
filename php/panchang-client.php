<?php
/**
 * DevDarsha Panchang API — PHP examples.
 *
 * Two ways to call the API:
 *   1. devdarsha_get_panchang_curl()    — plain PHP using the cURL extension.
 *   2. devdarsha_get_panchang_wp()      — WordPress using wp_remote_post().
 *
 * Run the plain-PHP example from the CLI:
 *   export DEVDARSHA_API_KEY=your_api_key_here
 *   php php/panchang-client.php
 *
 * Get a free API key at https://platform.devdarsha.com
 */

const DEVDARSHA_API_URL = 'https://panchang.devdarsha.com/v1/panchang/daily';

/**
 * Plain PHP (cURL). Returns the decoded response envelope:
 *   ['data' => [...], 'meta' => [...], 'dev_notes' => [...]]
 *
 * @throws RuntimeException on transport or API error.
 */
function devdarsha_get_panchang_curl(string $apiKey, string $date, string $cityId): array
{
    $payload = json_encode([
        'date'    => $date,
        'city_id' => $cityId, // city_id works on all plans; lat/lon needs Amethyst+
    ]);

    $ch = curl_init(DEVDARSHA_API_URL);
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
 * Returns the decoded envelope array, or a WP_Error on failure.
 *
 * @return array|WP_Error
 */
function devdarsha_get_panchang_wp(string $apiKey, string $date, string $cityId)
{
    $response = wp_remote_post(DEVDARSHA_API_URL, [
        'headers' => [
            'X-Api-Key'    => $apiKey, // key in header, not the body
            'Content-Type' => 'application/json',
        ],
        'body'    => wp_json_encode([
            'date'    => $date,
            'city_id' => $cityId, // city_id works on all plans; lat/lon needs Amethyst+
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

    return $result; // ['data' => ..., 'meta' => ..., 'dev_notes' => ...]
}

// ── CLI demo (only runs when executed directly, not when included) ───────────
if (PHP_SAPI === 'cli' && isset($argv) && realpath($argv[0]) === __FILE__) {
    $apiKey = getenv('DEVDARSHA_API_KEY');
    $cityId = getenv('DEVDARSHA_CITY_ID') ?: 'ujjain';
    $date   = getenv('DEVDARSHA_DATE') ?: '2026-04-15';

    if (!$apiKey) {
        fwrite(STDERR, "Missing DEVDARSHA_API_KEY. Get a free key at https://platform.devdarsha.com\n");
        exit(1);
    }

    try {
        $result = devdarsha_get_panchang_curl($apiKey, $date, $cityId);
        $data   = $result['data'];

        printf("Panchang for %s on %s (%s)\n", $data['city'], $data['date'], $data['weekday']);
        printf("  Tithi:     %s (%s)\n", $data['tithi'][0]['name'], $data['tithi'][0]['paksha']);
        printf("  Nakshatra: %s\n", $data['nakshatra'][0]['name']);
        printf("  Sunrise:   %s\n", $data['sun']['rise']);
        printf("  Sunset:    %s\n", $data['sun']['set']);
        printf("  API version (meta): %s\n", $result['meta']['version']);
    } catch (RuntimeException $e) {
        fwrite(STDERR, $e->getMessage() . "\n");
        exit(1);
    }
}
