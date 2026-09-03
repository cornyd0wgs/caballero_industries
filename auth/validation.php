<?php

function validate_name($name) {
    $name = trim($name);

    if ($name === '') {
        return false;
    }

    // Only letters, spaces, hyphens, apostrophes, and periods allowed.
    // If the string contains anything else (digits included), it fails.
    if (!preg_match("/^[\p{L}\s'\-\.]+$/u", $name)) {
        return false;
    }

    // Require at least 2 characters so a single letter isn't accepted
    if (strlen($name) < 2) {
        return false;
    }

    return true;
}

function validate_email($email) {
    $email = trim($email);
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function validate_age($age) {
    $age = trim((string) $age);

    if (!ctype_digit($age)) {
        return false;
    }

    $age = (int) $age;

    return $age >= 1 && $age <= 100;
}

function validate_password($password) {
    return strlen($password) >= 8;
}

function validate_number_range($value, $min, $max) {
    $value = trim((string) $value);

    if (!ctype_digit($value)) {
        return false;
    }

    $value = (int) $value;

    return $value >= $min && $value <= $max;
}

?>