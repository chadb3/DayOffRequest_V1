<?php

declare(strict_types=1);

/**
 * Format a stored submitted_at value for display.
 * Returns 'Unknown' for an empty string, otherwise the value unchanged.
 */
function formatSubmittedDate(string $value): string
{
    return $value === '' ? 'Unknown' : $value;
}
