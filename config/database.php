<?php
$dbPath = __DIR__ . '/../database/scid_billing.sqlite';

try {
    // Check if database file exists, if not create it
    if (!file_exists($dbPath)) {
        // Create database directory if it doesn't exist
        $dbDir = dirname($dbPath);
        if (!is_dir($dbDir)) {
            mkdir($dbDir, 0755, true);
        }
        
        // Create empty database file
        touch($dbPath);
        chmod($dbPath, 0644);
        
        // Initialize database with schema
        $schemaPath = __DIR__ . '/../database/schema.sqlite.sql';
        if (file_exists($schemaPath)) {
            $schema = file_get_contents($schemaPath);
            $tempConn = new PDO("sqlite:" . $dbPath);
            $tempConn->exec($schema);
            $tempConn = null;
        }
    }

    // Create database connection
    $conn = new PDO("sqlite:" . $dbPath);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    // Enable foreign key support
    $conn->exec('PRAGMA foreign_keys = ON;');
    
    // Test connection
    $conn->query('SELECT 1');
    
} catch(PDOException $e) {
    error_log("Database connection error: " . $e->getMessage());
    error_log("Database path: " . $dbPath);
    error_log("Stack trace: " . $e->getTraceAsString());
    die("Database connection failed. Please check the error logs for details.");
}

// Function to get database connection
function getConnection() {
    global $conn;
    return $conn;
}

// Function to begin transaction
function beginTransaction() {
    global $conn;
    return $conn->beginTransaction();
}

// Function to commit transaction
function commitTransaction() {
    global $conn;
    return $conn->commit();
}

// Function to rollback transaction
function rollbackTransaction() {
    global $conn;
    return $conn->rollBack();
}

// Function to check database connection
function checkDatabaseConnection() {
    global $conn;
    try {
        $conn->query('SELECT 1');
        return true;
    } catch (PDOException $e) {
        error_log("Database connection check failed: " . $e->getMessage());
        return false;
    }
}

// Function to escape string for SQL
function escapeString($string) {
    global $conn;
    return $conn->quote($string);
}

// Function to get last insert ID
function getLastInsertId() {
    global $conn;
    return $conn->lastInsertId();
}

// Function to execute query with parameters
function executeQuery($sql, $params = []) {
    global $conn;
    try {
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    } catch (PDOException $e) {
        error_log("Query execution error: " . $e->getMessage());
        error_log("SQL: " . $sql);
        error_log("Parameters: " . print_r($params, true));
        throw $e;
    }
}

// Check database connection on initialization
if (!checkDatabaseConnection()) {
    die("Database connection check failed. Please check the configuration.");
}
?>
