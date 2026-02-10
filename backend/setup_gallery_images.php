<?php
// Setup database table for gallery images
require_once __DIR__ . '/config.php';

try {
    $conn = getDatabaseConnection();
    
    // Create gallery_images table
    $sql = "CREATE TABLE IF NOT EXISTS gallery_images (
        id INT AUTO_INCREMENT PRIMARY KEY,
        image_url VARCHAR(500) NOT NULL,
        category ENUM('exterior', 'interior') NOT NULL,
        subcategory VARCHAR(50) DEFAULT NULL,
        title VARCHAR(200) NOT NULL,
        description TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_category (category)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    $conn->query($sql);
    
    echo "Gallery images table created successfully!<br>";
    
    // Check if table is empty, if so, populate with default images
    $result = $conn->query("SELECT COUNT(*) as count FROM gallery_images");
    $row = $result->fetch_assoc();
    
    if ($row['count'] == 0) {
        echo "Populating with ALL images from explore_designs.php...<br>";
        
        // ALL EXTERIOR IMAGES (20 images)
        $exteriorImages = [
            ['https://images.pexels.com/photos/106399/pexels-photo-106399.jpeg?auto=compress&cs=tinysrgb&w=600', 'Modern House Exterior 1', 'Beautiful house exterior design with modern architecture.'],
            ['https://images.pexels.com/photos/186077/pexels-photo-186077.jpeg?auto=compress&cs=tinysrgb&w=600', 'Modern House Exterior 2', 'Contemporary residential building with clean lines.'],
            ['https://images.pexels.com/photos/323780/pexels-photo-323780.jpeg?auto=compress&cs=tinysrgb&w=600', 'Modern House Exterior 3', 'Stylish modern home with elegant facade.'],
            ['https://images.pexels.com/photos/1396122/pexels-photo-1396122.jpeg?auto=compress&cs=tinysrgb&w=600', 'Modern House Exterior 4', 'Luxury house exterior with premium finishes.'],
            ['https://images.pexels.com/photos/1396132/pexels-photo-1396132.jpeg?auto=compress&cs=tinysrgb&w=600', 'Modern House Exterior 5', 'Modern villa with sophisticated design.'],
            ['https://images.pexels.com/photos/1438832/pexels-photo-1438832.jpeg?auto=compress&cs=tinysrgb&w=600', 'Modern House Exterior 6', 'Contemporary house with beautiful landscaping.'],
            ['https://images.pexels.com/photos/1475938/pexels-photo-1475938.jpeg?auto=compress&cs=tinysrgb&w=600', 'Modern House Exterior 7', 'Elegant residential architecture design.'],
            ['https://images.pexels.com/photos/1546168/pexels-photo-1546168.jpeg?auto=compress&cs=tinysrgb&w=600', 'Modern House Exterior 8', 'Modern home with stunning curb appeal.'],
            ['https://images.pexels.com/photos/1571460/pexels-photo-1571460.jpeg?auto=compress&cs=tinysrgb&w=600', 'Modern House Exterior 9', 'Beautiful house with modern architectural elements.'],
            ['https://images.pexels.com/photos/1643383/pexels-photo-1643383.jpeg?auto=compress&cs=tinysrgb&w=600', 'Modern House Exterior 10', 'Contemporary residential design with style.'],
            ['https://images.pexels.com/photos/1732414/pexels-photo-1732414.jpeg?auto=compress&cs=tinysrgb&w=600', 'Modern House Exterior 11', 'Luxury modern home exterior view.'],
            ['https://images.pexels.com/photos/2102587/pexels-photo-2102587.jpeg?auto=compress&cs=tinysrgb&w=600', 'Modern House Exterior 12', 'Stylish house with premium materials.'],
            ['https://images.pexels.com/photos/2121121/pexels-photo-2121121.jpeg?auto=compress&cs=tinysrgb&w=600', 'Modern House Exterior 13', 'Modern villa with elegant design.'],
            ['https://images.pexels.com/photos/2251247/pexels-photo-2251247.jpeg?auto=compress&cs=tinysrgb&w=600', 'Modern House Exterior 14', 'Contemporary house with beautiful facade.'],
            ['https://images.pexels.com/photos/2360673/pexels-photo-2360673.jpeg?auto=compress&cs=tinysrgb&w=600', 'Modern House Exterior 15', 'Modern residential architecture masterpiece.'],
            ['https://images.pexels.com/photos/2581922/pexels-photo-2581922.jpeg?auto=compress&cs=tinysrgb&w=600', 'Modern House Exterior 16', 'Elegant house with sophisticated styling.'],
            ['https://images.pexels.com/photos/2724748/pexels-photo-2724748.jpeg?auto=compress&cs=tinysrgb&w=600', 'Modern House Exterior 17', 'Contemporary home with premium finishes.'],
            ['https://images.pexels.com/photos/2747901/pexels-photo-2747901.jpeg?auto=compress&cs=tinysrgb&w=600', 'Modern House Exterior 18', 'Modern house with stunning design.'],
            ['https://images.pexels.com/photos/3288102/pexels-photo-3288102.png?auto=compress&cs=tinysrgb&w=600', 'Modern House Exterior 19', 'Beautiful modern residential building.'],
            ['https://images.pexels.com/photos/3555615/pexels-photo-3555615.jpeg?auto=compress&cs=tinysrgb&w=600', 'Modern House Exterior 20', 'Luxury modern home with elegant architecture.']
        ];
        
        // ALL LIVING ROOM IMAGES (20 images)
        $livingRoomImages = [
            ['https://images.pexels.com/photos/1457842/pexels-photo-1457842.jpeg?auto=compress&cs=tinysrgb&w=600', 'Living Room Design 1', 'Elegant living room interior with modern furnishings.'],
            ['https://images.pexels.com/photos/1648776/pexels-photo-1648776.jpeg?auto=compress&cs=tinysrgb&w=600', 'Living Room Design 2', 'Contemporary living space with stylish decor.'],
            ['https://images.pexels.com/photos/1743229/pexels-photo-1743229.jpeg?auto=compress&cs=tinysrgb&w=600', 'Living Room Design 3', 'Modern living room with comfortable seating.'],
            ['https://images.pexels.com/photos/1866149/pexels-photo-1866149.jpeg?auto=compress&cs=tinysrgb&w=600', 'Living Room Design 4', 'Luxurious living area with premium furniture.'],
            ['https://images.pexels.com/photos/2029667/pexels-photo-2029667.jpeg?auto=compress&cs=tinysrgb&w=600', 'Living Room Design 5', 'Bright and airy living room design.'],
            ['https://images.pexels.com/photos/2079249/pexels-photo-2079249.jpeg?auto=compress&cs=tinysrgb&w=600', 'Living Room Design 6', 'Cozy living space with modern aesthetics.'],
            ['https://images.pexels.com/photos/2082090/pexels-photo-2082090.jpeg?auto=compress&cs=tinysrgb&w=600', 'Living Room Design 7', 'Stylish living room with elegant touches.'],
            ['https://images.pexels.com/photos/2119714/pexels-photo-2119714.jpeg?auto=compress&cs=tinysrgb&w=600', 'Living Room Design 8', 'Contemporary living area with sophisticated design.'],
            ['https://images.pexels.com/photos/2132180/pexels-photo-2132180.jpeg?auto=compress&cs=tinysrgb&w=600', 'Living Room Design 9', 'Modern living room with beautiful lighting.'],
            ['https://images.pexels.com/photos/2227832/pexels-photo-2227832.jpeg?auto=compress&cs=tinysrgb&w=600', 'Living Room Design 10', 'Elegant living space with premium finishes.'],
            ['https://images.pexels.com/photos/2343468/pexels-photo-2343468.jpeg?auto=compress&cs=tinysrgb&w=600', 'Living Room Design 11', 'Luxurious living room with stylish furniture.'],
            ['https://images.pexels.com/photos/2440471/pexels-photo-2440471.jpeg?auto=compress&cs=tinysrgb&w=600', 'Living Room Design 12', 'Contemporary living area with modern decor.'],
            ['https://images.pexels.com/photos/2635038/pexels-photo-2635038.jpeg?auto=compress&cs=tinysrgb&w=600', 'Living Room Design 13', 'Bright living room with natural light.'],
            ['https://images.pexels.com/photos/2724749/pexels-photo-2724749.jpeg?auto=compress&cs=tinysrgb&w=600', 'Living Room Design 14', 'Modern living space with elegant design.'],
            ['https://images.pexels.com/photos/3209045/pexels-photo-3209045.jpeg?auto=compress&cs=tinysrgb&w=600', 'Living Room Design 15', 'Stylish living room with contemporary furniture.'],
            ['https://images.pexels.com/photos/3705539/pexels-photo-3705539.jpeg?auto=compress&cs=tinysrgb&w=600', 'Living Room Design 16', 'Cozy living area with warm ambiance.'],
            ['https://images.pexels.com/photos/4050290/pexels-photo-4050290.jpeg?auto=compress&cs=tinysrgb&w=600', 'Living Room Design 17', 'Modern living room with sophisticated styling.'],
            ['https://images.pexels.com/photos/4112236/pexels-photo-4112236.jpeg?auto=compress&cs=tinysrgb&w=600', 'Living Room Design 18', 'Elegant living space with premium materials.'],
            ['https://images.pexels.com/photos/4352247/pexels-photo-4352247.jpeg?auto=compress&cs=tinysrgb&w=600', 'Living Room Design 19', 'Contemporary living room with beautiful decor.'],
            ['https://images.pexels.com/photos/5490965/pexels-photo-5490965.jpeg?auto=compress&cs=tinysrgb&w=600', 'Living Room Design 20', 'Luxurious living area with modern design.']
        ];
        
        // ALL KITCHEN IMAGES (10 images)
        $kitchenImages = [
            ['https://images.pexels.com/photos/1599791/pexels-photo-1599791.jpeg?auto=compress&cs=tinysrgb&w=600', 'Kitchen Design 1', 'Contemporary kitchen interior with premium finishes.'],
            ['https://images.pexels.com/photos/1599821/pexels-photo-1599821.jpeg?auto=compress&cs=tinysrgb&w=600', 'Kitchen Design 2', 'Modern kitchen with sleek cabinetry.'],
            ['https://images.pexels.com/photos/2062426/pexels-photo-2062426.jpeg?auto=compress&cs=tinysrgb&w=600', 'Kitchen Design 3', 'Elegant kitchen with sophisticated design.'],
            ['https://images.pexels.com/photos/2089698/pexels-photo-2089698.jpeg?auto=compress&cs=tinysrgb&w=600', 'Kitchen Design 4', 'Stylish kitchen with modern appliances.'],
            ['https://images.pexels.com/photos/2724748/pexels-photo-2724748.jpeg?auto=compress&cs=tinysrgb&w=600', 'Kitchen Design 5', 'Contemporary kitchen with beautiful lighting.'],
            ['https://images.pexels.com/photos/2816458/pexels-photo-2816458.jpeg?auto=compress&cs=tinysrgb&w=600', 'Kitchen Design 6', 'Modern kitchen with premium materials.'],
            ['https://images.pexels.com/photos/3315291/pexels-photo-3315291.jpeg?auto=compress&cs=tinysrgb&w=600', 'Kitchen Design 7', 'Luxurious kitchen with elegant finishes.'],
            ['https://images.pexels.com/photos/3935350/pexels-photo-3935350.jpeg?auto=compress&cs=tinysrgb&w=600', 'Kitchen Design 8', 'Bright kitchen with natural light.'],
            ['https://images.pexels.com/photos/4099354/pexels-photo-4099354.jpeg?auto=compress&cs=tinysrgb&w=600', 'Kitchen Design 9', 'Contemporary kitchen with stylish design.'],
            ['https://images.pexels.com/photos/4846428/pexels-photo-4846428.jpeg?auto=compress&cs=tinysrgb&w=600', 'Kitchen Design 10', 'Modern kitchen with sophisticated styling.']
        ];
        
        // ALL BEDROOM IMAGES (10 images)
        $bedroomImages = [
            ['https://images.pexels.com/photos/164595/pexels-photo-164595.jpeg?auto=compress&cs=tinysrgb&w=600', 'Bedroom Design 1', 'Cozy bedroom interior with stylish decor.'],
            ['https://images.pexels.com/photos/271624/pexels-photo-271624.jpeg?auto=compress&cs=tinysrgb&w=600', 'Bedroom Design 2', 'Modern bedroom with comfortable furnishings.'],
            ['https://images.pexels.com/photos/271743/pexels-photo-271743.jpeg?auto=compress&cs=tinysrgb&w=600', 'Bedroom Design 3', 'Elegant bedroom with sophisticated design.'],
            ['https://images.pexels.com/photos/1454806/pexels-photo-1454806.jpeg?auto=compress&cs=tinysrgb&w=600', 'Bedroom Design 4', 'Contemporary bedroom with stylish decor.'],
            ['https://images.pexels.com/photos/1743226/pexels-photo-1743226.jpeg?auto=compress&cs=tinysrgb&w=600', 'Bedroom Design 5', 'Luxurious bedroom with premium materials.'],
            ['https://images.pexels.com/photos/2029670/pexels-photo-2029670.jpeg?auto=compress&cs=tinysrgb&w=600', 'Bedroom Design 6', 'Bright bedroom with natural lighting.'],
            ['https://images.pexels.com/photos/2082087/pexels-photo-2082087.jpeg?auto=compress&cs=tinysrgb&w=600', 'Bedroom Design 7', 'Modern bedroom with elegant touches.'],
            ['https://images.pexels.com/photos/2747901/pexels-photo-2747901.jpeg?auto=compress&cs=tinysrgb&w=600', 'Bedroom Design 8', 'Stylish bedroom with contemporary design.'],
            ['https://images.pexels.com/photos/3209049/pexels-photo-3209049.jpeg?auto=compress&cs=tinysrgb&w=600', 'Bedroom Design 9', 'Cozy bedroom with warm ambiance.'],
            ['https://images.pexels.com/photos/6585751/pexels-photo-6585751.jpeg?auto=compress&cs=tinysrgb&w=600', 'Bedroom Design 10', 'Modern bedroom with sophisticated styling.']
        ];
        
        $totalInserted = 0;
        
        // Insert all exterior images
        foreach ($exteriorImages as $img) {
            $stmt = $conn->prepare("INSERT INTO gallery_images (image_url, category, subcategory, title, description) VALUES (?, 'exterior', 'house', ?, ?)");
            $stmt->bind_param("sss", $img[0], $img[1], $img[2]);
            if ($stmt->execute()) {
                $totalInserted++;
            }
        }
        
        // Insert all living room images
        foreach ($livingRoomImages as $img) {
            $stmt = $conn->prepare("INSERT INTO gallery_images (image_url, category, subcategory, title, description) VALUES (?, 'interior', 'living_room', ?, ?)");
            $stmt->bind_param("sss", $img[0], $img[1], $img[2]);
            if ($stmt->execute()) {
                $totalInserted++;
            }
        }
        
        // Insert all kitchen images
        foreach ($kitchenImages as $img) {
            $stmt = $conn->prepare("INSERT INTO gallery_images (image_url, category, subcategory, title, description) VALUES (?, 'interior', 'kitchen', ?, ?)");
            $stmt->bind_param("sss", $img[0], $img[1], $img[2]);
            if ($stmt->execute()) {
                $totalInserted++;
            }
        }
        
        // Insert all bedroom images
        foreach ($bedroomImages as $img) {
            $stmt = $conn->prepare("INSERT INTO gallery_images (image_url, category, subcategory, title, description) VALUES (?, 'interior', 'bedroom', ?, ?)");
            $stmt->bind_param("sss", $img[0], $img[1], $img[2]);
            if ($stmt->execute()) {
                $totalInserted++;
            }
        }
        
        echo "<strong>Successfully inserted $totalInserted images!</strong><br>";
        echo "- 20 Exterior images<br>";
        echo "- 20 Living Room images<br>";
        echo "- 10 Kitchen images<br>";
        echo "- 10 Bedroom images<br>";
        echo "<br><strong>Total: 60 images</strong><br>";
    } else {
        echo "Database already contains {$row['count']} images. Skipping population.<br>";
    }
    
    echo "<br><a href='../content.php' style='display:inline-block;padding:12px 24px;background:#0284c7;color:white;text-decoration:none;border-radius:8px;font-weight:bold;'>Go to Content Management</a>";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}

$conn->close();
?>
