<?php
    // Read DB configuration from environment variables (set by Docker Compose or .env)
    $host = getenv('MYSQL_HOST');
    $username = getenv('MYSQL_USER');
    $password = getenv('MYSQL_PASSWORD');
    $dbname = getenv('MYSQL_DATABASE');

    try {
        // Connexion avec PDO MySQL
        $db = new PDO("mysql:host={$host};dbname={$dbname};charset=utf8mb4", $username, $password);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // In a containerized dev environment it's helpful to see the error
        echo "Database connection error: " . htmlspecialchars($e->getMessage());
        exit;
    }
?>