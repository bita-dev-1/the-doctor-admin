<?php
// update_slugs.php

// 1. Load Core Configuration & DB
require_once __DIR__ . '/inc.php';

if (file_exists(__DIR__ . '/config/DB.php')) {
    require_once __DIR__ . '/config/DB.php';
} else {
    die("Database configuration missing.");
}

// 2. Initialize DB
try {
    $db = new DB();
} catch (Exception $e) {
    die("Database Error: " . $e->getMessage());
}

echo "<h1>Starting Slug Update Process...</h1>";

// 3. Fetch users with empty slugs
// We target doctors specifically, or all users depending on your need. 
// Here we target all users to be safe.
$sql = "SELECT id, first_name, last_name FROM users WHERE landing_slug IS NULL OR landing_slug = ''";
$users = $db->select($sql);

if (empty($users)) {
    echo "<p>No users found needing updates.</p>";
    exit();
}

$count = 0;
$errors = 0;

foreach ($users as $user) {
    $id = $user['id'];
    $firstName = $user['first_name'] ?? '';
    $lastName = $user['last_name'] ?? '';

    // 4. Generate Slug Logic (Name-Surname-ID)
    // This pattern guarantees uniqueness because ID is unique
    $rawSlug = $firstName . '-' . $lastName . '-' . $id;

    // 5. Sanitize Slug (URL Friendly)
    // Convert to lowercase
    $slug = strtolower($rawSlug);
    // Replace accented characters (Basic handling)
    $slug = str_replace(
        ['à', 'á', 'â', 'ã', 'ä', 'ç', 'è', 'é', 'ê', 'ë', 'ì', 'í', 'î', 'ï', 'ñ', 'ò', 'ó', 'ô', 'õ', 'ö', 'ù', 'ú', 'û', 'ü', 'ý', 'ÿ'],
        ['a', 'a', 'a', 'a', 'a', 'c', 'e', 'e', 'e', 'e', 'i', 'i', 'i', 'i', 'n', 'o', 'o', 'o', 'o', 'o', 'u', 'u', 'u', 'u', 'y', 'y'],
        $slug
    );
    // Remove special characters (keep only letters, numbers, and dashes)
    $slug = preg_replace('/[^a-z0-9-]/', '-', $slug);
    // Remove duplicate dashes
    $slug = preg_replace('/-+/', '-', $slug);
    // Trim dashes from start/end
    $slug = trim($slug, '-');

    // Fallback if slug becomes empty (rare edge case)
    if (empty($slug)) {
        $slug = 'user-' . $id;
    }

    // 6. Update Database
    try {
        $db->table = 'users';
        $db->data = ['landing_slug' => $slug];
        $db->where = "id = $id";

        if ($db->update()) {
            echo "<p style='color:green;'>Updated User ID: $id | Slug: <strong>$slug</strong></p>";
            $count++;
        } else {
            echo "<p style='color:red;'>Failed to update User ID: $id</p>";
            $errors++;
        }
    } catch (Exception $e) {
        echo "<p style='color:red;'>Error updating User ID: $id - " . $e->getMessage() . "</p>";
        $errors++;
    }
}

echo "<hr>";
echo "<h3>Process Completed.</h3>";
echo "<p><strong>Total Updated:</strong> $count</p>";
echo "<p><strong>Errors:</strong> $errors</p>";

$db = null;
?>