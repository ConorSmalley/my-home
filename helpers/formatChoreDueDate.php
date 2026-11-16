<?php
/**
 * Format a chore due date in a child-friendly format.
 *
 * Examples:
 * Today at 4
 * Tomorrow at 5
 * Saturday at 11
 */

function formatChoreDueDate(string $date): string
{
    $timezone = new DateTimeZone('Europe/London');

    $dueDate = new DateTimeImmutable($date, $timezone);
    $today = new DateTimeImmutable('today', $timezone);
    $tomorrow = $today->modify('+1 day');

    $dueDay = $dueDate->format('Y-m-d');

    if ($dueDay === $today->format('Y-m-d')) {
        $day = 'Today';
    } elseif ($dueDay === $tomorrow->format('Y-m-d')) {
        $day = 'Tomorrow';
    } else {
        $day = $dueDate->format('l');
    }

    // Format hour without leading zero or minutes.
    $time = $dueDate->format('g:i a');

    return $day . ' at ' . $time;
}
?>