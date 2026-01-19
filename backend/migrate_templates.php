<?php
require_once 'config.php';
$conn = getDatabaseConnection();

// Templates from recommended_plans_wizard.php
$plans = [
    [ 'title' => 'Modern Compact Villa', 'area' => 1200, 'baseCost' => 2500000, 'floors' => 2, 'beds' => 3, 'style' => 'Modern', 'image' => 'uploads/plans/modern_villa.png', 'reasoning' => ['Optimal for 30x40 Plot', 'High Rental Yield', 'Vastu Neutral'] ],
    [ 'title' => 'Traditional Courtyard Home', 'area' => 1500, 'baseCost' => 3200000, 'floors' => 1, 'beds' => 3, 'style' => 'Traditional', 'image' => 'uploads/plans/traditional_house.png', 'reasoning' => ['Perfect for South Façade', 'Elderly Friendly', 'Eco-Materials'] ],
    [ 'title' => 'Urban Duplex', 'area' => 1000, 'baseCost' => 2800000, 'floors' => 2, 'beds' => 2, 'style' => 'Modern', 'image' => 'uploads/plans/urban_duplex.png', 'reasoning' => ['Compact City Design', 'Smart Storage', 'Low Maintenance'] ],
    [ 'title' => 'Vastu Compliant 3BHK', 'area' => 1800, 'baseCost' => 4000000, 'floors' => 2, 'beds' => 3, 'style' => 'Vastu', 'image' => 'uploads/plans/vastu_mansion.png', 'reasoning' => ['100% Vastu Score', 'Max Natural Light', 'Resale Value High'] ],
    [ 'title' => 'Modern Ground Villa', 'area' => 1200, 'baseCost' => 2000000, 'floors' => 1, 'beds' => 2, 'style' => 'Modern', 'image' => 'uploads/plans/modern_villa.png', 'reasoning' => ['Single Floor Comfort', 'Open Plan Living', 'Budget Friendly'] ],
    [ 'title' => 'Grand Vastu Mansion', 'area' => 2400, 'baseCost' => 6000000, 'floors' => 2, 'beds' => 4, 'style' => 'Vastu', 'image' => 'uploads/plans/vastu_mansion.png', 'reasoning' => ['Luxury Living', 'Large Garden Space', 'Premium Assessment'] ],
    [ 'title' => 'Traditional G+2 Joint Family', 'area' => 1500, 'baseCost' => 5500000, 'floors' => 3, 'beds' => 5, 'style' => 'Traditional', 'image' => 'uploads/plans/traditional_house.png', 'reasoning' => ['Multi-Gen Living', 'Separate Floor Units', 'Terrace Garden'] ],
    [ 'title' => 'Traditional Starter Home', 'area' => 1200, 'baseCost' => 2200000, 'floors' => 1, 'beds' => 2, 'style' => 'Traditional', 'image' => 'uploads/plans/traditional_house.png', 'reasoning' => ['Starter Choice', 'Porch Included', 'Low Cost'] ],
    [ 'title' => 'Vastu Duplex 1200', 'area' => 1200, 'baseCost' => 2800000, 'floors' => 2, 'beds' => 3, 'style' => 'Vastu', 'image' => 'uploads/plans/vastu_mansion.png', 'reasoning' => ['N-E Entrance', 'Double Height Hall', 'Pooja Room'] ],
    [ 'title' => 'Compact Modern G', 'area' => 1000, 'baseCost' => 1800000, 'floors' => 1, 'beds' => 2, 'style' => 'Modern', 'image' => 'uploads/plans/urban_duplex.png', 'reasoning' => ['Cube Design', 'Minimalist', 'Efficient'] ],
    [ 'title' => 'Spacious Vastu Ground', 'area' => 1500, 'baseCost' => 2500000, 'floors' => 1, 'beds' => 3, 'style' => 'Vastu', 'image' => 'uploads/plans/vastu_mansion.png', 'reasoning' => ['Brahmasthan Center', 'East Facet', 'Ventilated'] ],
    [ 'title' => 'Traditional Villa G+1', 'area' => 1800, 'baseCost' => 4500000, 'floors' => 2, 'beds' => 4, 'style' => 'Traditional', 'image' => 'uploads/plans/traditional_house.png', 'reasoning' => ['Classic Aesthetic', 'Wood Finishes', 'Large Living'] ],
    [ 'title' => 'Luxury Modern G+2', 'area' => 2400, 'baseCost' => 7500000, 'floors' => 3, 'beds' => 5, 'style' => 'Modern', 'image' => 'uploads/plans/urban_duplex.png', 'reasoning' => ['Penthouse Suite', 'Infinity Pool', 'Glass Walls'] ]
];

// Truncate to reset
$conn->query("TRUNCATE TABLE house_templates");

// Prepared statement with CORRECT types
// title(s), desc(s), image(s), area(i), floors(i), style(s), min(d), max(d), specs(s)
$stmt = $conn->prepare("INSERT INTO house_templates (title, description, image_url, area_sqft, floors, style, budget_min, budget_max, specifications, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1)");

foreach ($plans as $p) {
    $title = $p['title'];
    $desc = implode(', ', $p['reasoning']);
    $img = $p['image'];
    $area = $p['area'];
    $floors = $p['floors'];
    $style = $p['style'];
    $min = $p['baseCost'];
    $max = $min * 1.15; // +15%
    $specs = "{$p['beds']} BHK, {$p['floors']} Floors";

    // "sssiisdds"
    $stmt->bind_param("sssiisdds", $title, $desc, $img, $area, $floors, $style, $min, $max, $specs);
    
    if ($stmt->execute()) {
        echo "Inserted: $title\n";
    } else {
        echo "Error: " . $stmt->error . "\n";
    }
}

$conn->close();
?>
