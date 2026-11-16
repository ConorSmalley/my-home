
<?php

/**
 * Make a GET request to the Grocy API.
 */
function grocyGet(string $endpoint, string $apiKey): array
{
    $grocyUrl = rtrim($_ENV['GROCY_URL'], '/');

    $ch = curl_init($grocyUrl . $endpoint);

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Accept: application/json',
            'GROCY-API-KEY: ' . $apiKey,
        ],
        CURLOPT_TIMEOUT => 10,
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if ($response === false) {
        $error = curl_error($ch);
        curl_close($ch);

        throw new RuntimeException('Grocy API error: ' . $error);
    }

    curl_close($ch);

    if ($httpCode < 200 || $httpCode >= 300) {
        throw new RuntimeException(
            "Grocy returned HTTP {$httpCode}: {$response}"
        );
    }

    $data = json_decode($response, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new RuntimeException(
            'Invalid Grocy JSON: ' . json_last_error_msg()
        );
    }

    return $data;
}

/**
 * Get the current Grocy user's ID.
 */
function getGrocyUserId(string $apiKey): int
{
    $users = grocyGet('/api/user', $apiKey);

    $userId = $users[0]['id'] ?? null;

    if ($userId === null) {
        throw new RuntimeException('Unable to retrieve Grocy user ID');
    }

    return (int) $userId;
}

/**
 * Get chores assigned to a user within an optional date range.
 *
 * $from = Inclusive start date/time.
 * $to   = Exclusive end date/time.
 */
function getGrocyChores(
    string $apiKey,
    ?DateTimeImmutable $from = null,
    ?DateTimeImmutable $to = null
): array {

    $userId = getGrocyUserId($apiKey);

    $filters = [
        'next_execution_assigned_to_user_id=' . $userId
    ];

    if ($from !== null) {
        $filters[] = 'next_estimated_execution_time>=' .
            $from->format('Y-m-d H:i:s');
    }

    if ($to !== null) {
        $filters[] = 'next_estimated_execution_time<' .
            $to->format('Y-m-d H:i:s');
    }

    $query = http_build_query([
        'query' => $filters
    ]);

    return grocyGet('/api/chores?' . $query, $apiKey);
}

/**
 * Get overdue chores.
 */
function getOverdueGrocyChores(string $apiKey): array
{
    $now = new DateTimeImmutable(
        'now',
        new DateTimeZone('Europe/London')
    );

    return getGrocyChores(
        $apiKey,
        null,
        $now
    );
}

/**
 * Get today's chores.
 */
function getTodaysGrocyChores(string $apiKey): array
{
    $timezone = new DateTimeZone('Europe/London');

    $today = new DateTimeImmutable('today', $timezone);
    $tomorrow = $today->modify('+1 day');

    return getGrocyChores(
        $apiKey,
        $today,
        $tomorrow
    );
}

/**
 * Get chores that aren't assigned to anyone.
 */
function getUnassignedGrocyChores(string $apiKey): array
{
    $query = http_build_query([
        'query' => [
            'next_execution_assigned_to_user_id=0'
        ]
    ]);

    return grocyGet('/api/chores?' . $query, $apiKey);
}

/**
 * Get an individual chore from Grocy.
 */
function getGrocyChore(int $choreId, string $apiKey): array
{
    return grocyGet('/api/chores/' . $choreId, $apiKey);
}