<?php
/**
 * Simple API Test File
 * This file helps verify that the API is working correctly
 */

// Test database connection
require_once __DIR__ . '/../config/db.php';

echo "<h1>PetCare API Test</h1>";
echo "<h2>Database Connection</h2>";

if (isset($conn) && $conn->ping()) {
    echo "<p style='color: green;'>✓ Database connection successful!</p>";
    
    // Test queries
    echo "<h2>Database Tables</h2>";
    $tables = ['doctors', 'services', 'bookings', 'blogs', 'contacts', 'testimonials'];
    
    foreach ($tables as $table) {
        $result = $conn->query("SHOW TABLES LIKE '$table'");
        if ($result && $result->num_rows > 0) {
            $count = $conn->query("SELECT COUNT(*) as count FROM $table")->fetch_assoc()['count'];
            echo "<p style='color: green;'>✓ Table '$table' exists ($count records)</p>";
        } else {
            echo "<p style='color: red;'>✗ Table '$table' does not exist</p>";
        }
    }
    
    echo "<h2>API Endpoints</h2>";
    echo "<p>Test the following endpoints:</p>";
    echo "<ul>";
    echo "<li><a href='doctors.php'>GET /api/doctors.php</a></li>";
    echo "<li><a href='doctors.php?id=1'>GET /api/doctors.php?id=1</a></li>";
    echo "<li><a href='services.php'>GET /api/services.php</a></li>";
    echo "<li><a href='blogs.php'>GET /api/blogs.php</a></li>";
    echo "<li><a href='testimonials.php'>GET /api/testimonials.php</a></li>";
    echo "</ul>";
    
    echo "<h2>Sample Data</h2>";
    
    // Show sample doctors
    $doctors = getResults("SELECT id, name FROM doctors LIMIT 3");
    if (!empty($doctors)) {
        echo "<h3>Doctors:</h3><ul>";
        foreach ($doctors as $doctor) {
            echo "<li>ID: {$doctor['id']} - {$doctor['name']}</li>";
        }
        echo "</ul>";
    }
    
    // Show sample services
    $services = getResults("SELECT id, name FROM services LIMIT 3");
    if (!empty($services)) {
        echo "<h3>Services:</h3><ul>";
        foreach ($services as $service) {
            echo "<li>ID: {$service['id']} - {$service['name']}</li>";
        }
        echo "</ul>";
    }
    
} else {
    echo "<p style='color: red;'>✗ Database connection failed!</p>";
    echo "<p>Please check your database configuration in config/db.php</p>";
}

echo "<hr>";
echo "<p><a href='../index.php'>← Back to Homepage</a></p>";
?>

