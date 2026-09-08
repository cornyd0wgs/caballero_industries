<?php

/**
 * Shared helpers for the admin product forms.
 * Add Product and Edit Product both use these functions so validation
 * and image-upload rules only have to be maintained in one place.
 */

function validate_product_values(array $values): array
{
    $errors = [];

    if ($values['product_code'] === '') {
        $errors[] = 'Product code is required.';
    }

    if ($values['name'] === '') {
        $errors[] = 'Product name is required.';
    }

    if (!is_numeric($values['price']) || (float) $values['price'] <= 0) {
        $errors[] = 'Price must be a number greater than 0.';
    }

    if (
        $values['discount_price'] !== '' &&
        (!is_numeric($values['discount_price']) || (float) $values['discount_price'] <= 0)
    ) {
        $errors[] = 'Discount price must be a number greater than 0 (or left blank).';
    }

    if (
        $values['discount_price'] !== '' &&
        is_numeric($values['price']) &&
        (float) $values['discount_price'] >= (float) $values['price']
    ) {
        $errors[] = 'Discount price must be lower than the regular price.';
    }

    if (!validate_number_range($values['quantity'], 0, 100000)) {
        $errors[] = 'Quantity must be a whole number of 0 or more.';
    }

    return $errors;
}

function product_code_exists(PDO $conn, string $product_code, ?int $exclude_id = null): bool
{
    if ($exclude_id !== null) {
        $stmt = $conn->prepare(
            'SELECT id FROM products WHERE product_code = ? AND id != ?'
        );
        $stmt->execute([$product_code, $exclude_id]);
    } else {
        $stmt = $conn->prepare(
            'SELECT id FROM products WHERE product_code = ?'
        );
        $stmt->execute([$product_code]);
    }

    return (bool) $stmt->fetch();
}

function save_product_image(array $file, string $current_image = 'images/box.png'): array
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return ['path' => $current_image, 'error' => null];
    }

    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        return [
            'path' => $current_image,
            'error' => 'There was a problem uploading the image. Please try again.',
        ];
    }

    $allowed_extensions = ['jpg', 'jpeg', 'png', 'webp'];
    $allowed_mime_types = ['image/jpeg', 'image/png', 'image/webp'];

    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $mime_type = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);

    if (
        !in_array($extension, $allowed_extensions, true) ||
        !in_array($mime_type, $allowed_mime_types, true)
    ) {
        return [
            'path' => $current_image,
            'error' => 'Image must be a real JPG, PNG, or WEBP file.',
        ];
    }

    if ($file['size'] > 5 * 1024 * 1024) {
        return [
            'path' => $current_image,
            'error' => 'Image must be smaller than 5MB.',
        ];
    }

    $upload_dir = __DIR__ . '/../images/products/';

    if (!is_dir($upload_dir) && !mkdir($upload_dir, 0755, true)) {
        return [
            'path' => $current_image,
            'error' => 'Could not create the product image folder.',
        ];
    }

    $safe_filename = uniqid('product_', true) . '.' . $extension;
    $destination = $upload_dir . $safe_filename;

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        return [
            'path' => $current_image,
            'error' => 'Could not save the uploaded image.',
        ];
    }

    return [
        'path' => 'images/products/' . $safe_filename,
        'error' => null,
    ];
}
