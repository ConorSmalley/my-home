
<?php

/**
 * Generate realistic dummy Grocy chore data.
 */
function getMockGrocyChores(): array
{
    $timezone = new DateTimeZone('Europe/London');
    $now = new DateTimeImmutable('now', $timezone);
    $today = new DateTimeImmutable('today', $timezone);

    return [
        [
            'chore_id' => 1,
            'chore_name' => 'Wash the dishes',
            'description' => 'Wash and put away all dishes.',
            'next_execution_assigned_to_user_id' => 1,
            'next_estimated_execution_time' => $today->modify('-1 day')->format('Y-m-d H:i:s'),
            'last_tracked_time' => null,
        ],
        [
            'chore_id' => 2,
            'chore_name' => 'Empty dishwasher',
            'description' => 'Empty the dishwasher and put everything away.',
            'next_execution_assigned_to_user_id' => 1,
            'next_estimated_execution_time' => $today->setTime(10, 0)->format('Y-m-d H:i:s'),
            'last_tracked_time' => null,
        ],
        [
            'chore_id' => 3,
            'chore_name' => 'Feed the pets',
            'description' => 'Feed the pets and refresh their water.',
            'next_execution_assigned_to_user_id' => 1,
            'next_estimated_execution_time' => $today->setTime(20, 0)->format('Y-m-d H:i:s'),
            'last_tracked_time' => null,
        ],
        [
            'chore_id' => 4,
            'chore_name' => 'Take the bins out',
            'description' => 'Take the rubbish and recycling bins outside.',
            'next_execution_assigned_to_user_id' => 1,
            'next_estimated_execution_time' => $today->modify('+1 day')->setTime(18, 0)->format('Y-m-d H:i:s'),
            'last_tracked_time' => null,
        ],
        [
            'chore_id' => 5,
            'chore_name' => 'Vacuum downstairs',
            'description' => 'Vacuum the living room, hallway and kitchen.',
            'next_execution_assigned_to_user_id' => 1,
            'next_estimated_execution_time' => $today->modify('+3 days')->setTime(9, 0)->format('Y-m-d H:i:s'),
            'last_tracked_time' => null,
        ],
        [
            'chore_id' => 6,
            'chore_name' => 'Clean bathroom',
            'description' => 'Clean the bathroom, including the sink and toilet.',
            'next_execution_assigned_to_user_id' => null,
            'next_estimated_execution_time' => $today->format('Y-m-d H:i:s'),
            'last_tracked_time' => null,
        ],
        [
            'chore_id' => 7,
            'chore_name' => 'Clean kitchen',
            'description' => 'Wipe down all kitchen surfaces.',
            'next_execution_assigned_to_user_id' => null,
            'next_estimated_execution_time' => $today->format('Y-m-d H:i:s'),
            'last_tracked_time' => $now->modify('-14 hours')->format('Y-m-d H:i:s'),
        ],
        [
            'chore_id' => 8,
            'chore_name' => 'Clean living room',
            'description' => 'Tidy and clean the living room.',
            'next_execution_assigned_to_user_id' => null,
            'next_estimated_execution_time' => $today->format('Y-m-d H:i:s'),
            'last_tracked_time' => $now->modify('-3 hours')->format('Y-m-d H:i:s'),
        ],
    ];
}

/**
 * Apply Grocy-style query filters to mock chores.
 */
function filterMockGrocyChores(array $chores, array $filters): array
{
    foreach ($filters as $filter) {
        if (!preg_match('/^(.+?)(>=|<=|>|<|=)(.*)$/', $filter, $matches)) {
            continue;
        }

        [, $field, $operator, $value] = $matches;

        $chores = array_filter($chores, function ($chore) use (
            $field,
            $operator,
            $value
        ) {
            $actual = $chore[$field] ?? null;

            if ($value === 'null') {
                return $operator === '='
                    ? $actual === null || $actual === ''
                    : $actual !== null && $actual !== '';
            }

            if ($actual === null) {
                return false;
            }

            return match ($operator) {
                '='  => (string) $actual === $value,
                '>'  => (string) $actual > $value,
                '<'  => (string) $actual < $value,
                '>=' => (string) $actual >= $value,
                '<=' => (string) $actual <= $value,
                default => true,
            };
        });
    }

    return array_values($chores);
}

/**
 * Handle mock Grocy GET requests.
 */
function grocyMockGet(string $endpoint): array
{
    $path = parse_url($endpoint, PHP_URL_PATH);
    $queryString = parse_url($endpoint, PHP_URL_QUERY);

    parse_str($queryString ?? '', $params);

    if ($path === '/api/user') {
        return [
            ['id' => 1, 'username' => 'conor']
        ];
    }

    if ($path === '/api/chores') {
        $chores = getMockGrocyChores();

        return filterMockGrocyChores(
            $chores,
            $params['query'] ?? []
        );
    }

    if (preg_match('#^/api/chores/(\d+)$#', $path, $matches)) {
        $choreId = (int) $matches[1];

        foreach (getMockGrocyChores() as $chore) {
            if ($chore['chore_id'] === $choreId) {
                return [
                    'chore' => [
                        'id' => $choreId,
                        'name' => $chore['chore_name'],
                        'description' => $chore['description'],
                    ]
                ];
            }
        }

        throw new RuntimeException("Mock chore {$choreId} not found");
    }

    throw new RuntimeException("Unsupported mock Grocy endpoint: {$endpoint}");
}

/**
 * Handle mock Grocy POST requests.
 */
function grocyMockPost(string $endpoint, array $data = []): array
{
    if (preg_match('#^/api/chores/(\d+)/execute$#', $endpoint, $matches)) {
        return [
            'success' => true,
            'message' => 'Mock chore completed successfully',
            'chore_id' => (int) $matches[1],
        ];
    }

    throw new RuntimeException("Unsupported mock Grocy endpoint: {$endpoint}");
}
