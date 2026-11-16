<?php

declare(strict_types=1);

if (!defined('ALLOWED_REASONS')) {
    define('ALLOWED_REASONS', ['Medical', 'Dental', 'Family Emergency', 'Personal', 'Other']);
}

/**
 * Validate a day-off request submission. Pure function, no database access.
 *
 * @param array<string,mixed> $input keys: name, phone, shifts, sub, reason
 * @return array<string,string> field => error message; empty array means valid
 */
function validateRequest(array $input): array
{
    $labels = [
        'name' => 'Name',
        'phone' => 'Phone',
        'shifts' => 'Shifts',
        'sub' => 'Substitute',
        'reason' => 'Reason',
    ];
    $errors = [];

    foreach ($labels as $field => $label) {
        $value = $input[$field] ?? '';
        if (!is_string($value) || trim($value) === '') {
            $errors[$field] = $label . ' is required.';
        }
    }

    if (!isset($errors['reason']) && !in_array($input['reason'], ALLOWED_REASONS, true)) {
        $errors['reason'] = 'Invalid reason selected.';
    }

    return $errors;
}
