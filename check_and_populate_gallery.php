<?php
require_once 'backend/config.php';

echo "=== Gallery Images Database Check ===\n\n";

// Check if table exists
$tableCheck = $conn->query("SHOW TABLES LIKE 'gallery_images'");
if ($tableCheck->num_rows == 0) {
    echo "❌ Table 'gallery_images' does not exist. Running setup...\n\n";
    include 'backend/setup_gallery_images.php';
    exit;
}

echo "✅ Table 'gallery_images' exists\n\n";

// Count images
$result = $conn->query("SELECT COUNT(*) as count FROM gallery_images");
$row = $result->fetch_assoc();
$count = $row['count'];

echo "Current image count: $count\n\n";

if ($count == 0) {
    echo "⚠️ Database is empty. Populating with 60 images...\n\n";
    include 'backend/setup_gallery_images.php';
} else {
    echo "✅ Database already has images\n\n";
    
    // Show breakdown
    $exteriors = $conn->query("SELECT COUNT(*) as count FROM gallery_images WHERE category = 'exterior'")->fetch_assoc()['count'];
    $interiors = $conn->query("SELECT COUNT(*) as count FROM gallery_images WHERE category = 'interior'")->fetch_assoc()['count'];
    
    echo "Breakdown:\n";
    echo "- Exteriors: $exteriors\n";
    echo "- Interiors: $interiors\n\n";
    
    echo "✅ All images are available in:\n";
    echo "   - explore_designs.php (hardcoded - 60 images)\n";
    echo "   - content.php (from database - $count images)\n\n";
    
    if ($count < 60) {
        echo "⚠️ Warning: Database has fewer images than explore_designs.php\n";
        echo "   You may want to run backend/setup_gallery_images.php to add more\n";
    }
}

$conn->close();
?>
