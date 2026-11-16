
<?php
require_once 'mock.php';
function shouldUseMockGrocy(int $curlErrorNumber): bool
{
    return ($_ENV['APP_ENV'] ?? 'production') === 'development'
        && in_array($curlErrorNumber, [
            CURLE_COULDNT_RESOLVE_HOST,
            CURLE_COULDNT_CONNECT,
        ], true);
}

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

    // if ($response === false) {
    //     $error = curl_error($ch);
    //     curl_close($ch);

    //     throw new RuntimeException('Grocy API error: ' . $error);
    // }

    if ($response === false) {
        $error = curl_error($ch);
        $errorNumber = curl_errno($ch);

        curl_close($ch);

        if (shouldUseMockGrocy($errorNumber)) {
            $_SESSION['using_mock_grocy'] = true;
            return grocyMockGet($endpoint);
        }

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
 * Make a POST request to the Grocy API.
 */
function grocyPost(string $endpoint, string $apiKey, array $data = []): array
{
    $grocyUrl = rtrim($_ENV['GROCY_URL'], '/');

    $ch = curl_init($grocyUrl . $endpoint);

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_HTTPHEADER => [
            'Accept: application/json',
            'Content-Type: application/json',
            'GROCY-API-KEY: ' . $apiKey,
        ],
        CURLOPT_TIMEOUT => 10,
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    // if ($response === false) {
    //     $error = curl_error($ch);
    //     curl_close($ch);

    //     throw new RuntimeException('Grocy API error: ' . $error);
    // }

    if ($response === false) {
        $error = curl_error($ch);
        $errorNumber = curl_errno($ch);

        curl_close($ch);

        if (shouldUseMockGrocy($errorNumber)) {
            $_SESSION['using_mock_grocy'] = true;
            return grocyMockPost($endpoint, $data);
        }

        throw new RuntimeException('Grocy API error: ' . $error);
    }
    curl_close($ch);

    if ($httpCode < 200 || $httpCode >= 300) {
        throw new RuntimeException(
            "Grocy returned HTTP {$httpCode}: {$response}"
        );
    }

    if ($response === '') {
        return [];
    }

    $result = json_decode($response, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new RuntimeException(
            'Invalid Grocy JSON: ' . json_last_error_msg()
        );
    }

    return $result ?? [];
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

    $chores = grocyGet('/api/chores?' . $query, $apiKey);

    // Retrieve additional details for each chore.
    foreach ($chores as &$chore) {
        $details = getGrocyChore(
            (int) $chore['chore_id'],
            $apiKey
        );
        $chore['description'] = $details['chore']['description'] ?? '';
    }

    unset($chore);

    return $chores;
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
 * Get tomorrows's chores.
 */
function getUpcommingGrocyChores(string $apiKey): array
{
    $timezone = new DateTimeZone('Europe/London');

    $today = new DateTimeImmutable('today', $timezone);
    $tomorrow = $today->modify('+1 day');
    $tomorrowPlus3 = $tomorrow->modify('+3 day');

    return getGrocyChores(
        $apiKey,
        $tomorrow,
        $tomorrowPlus3
    );
}

/**
 * Get chores that aren't assigned to anyone.
 */
/**
 * Get chores that aren't assigned to anyone,
 * excluding chores tracked within the last 12 hours.
 */
function getUnassignedGrocyChores(string $apiKey): array
{
    $query = http_build_query([
        'query' => [
            'next_execution_assigned_to_user_id=null'
        ]
    ]);

    $chores = grocyGet('/api/chores?' . $query, $apiKey);

    $timezone = new DateTimeZone('Europe/London');

    $cutoff = new DateTimeImmutable('now', $timezone)
        ->modify('-12 hours');

    return array_values(array_filter($chores, function ($chore) use ($cutoff, $timezone) {

        // Include chores that have never been tracked.
        if (empty($chore['last_tracked_time'])) {
            return true;
        }

        $lastTracked = new DateTimeImmutable(
            $chore['last_tracked_time'],
            $timezone
        );

        // Exclude chores tracked within the last 12 hours.
        return $lastTracked <= $cutoff;
    }));
}

/**
 * Get an individual chore from Grocy.
 */
function getGrocyChore(int $choreId, string $apiKey): array
{
    return grocyGet('/api/chores/' . $choreId, $apiKey);
}


/**
 * Mark a chore as completed in Grocy.
 */
function completeGrocyChore(int $choreId, string $apiKey): array
{
    return grocyPost(
        '/api/chores/' . $choreId . '/execute',
        $apiKey,
        []
    );
}
