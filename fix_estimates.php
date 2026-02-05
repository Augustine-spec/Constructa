<?php
$file_path = 'c:\xampp\htdocs\Constructa\active_estimates.php';
$lines = file($file_path);

$start_marker = 'STEP 4: AI STUDY EXPERIENCE';
$end_marker = 'function formatMoney(amount) {';

$start_idx = -1;
$end_idx = -1;

foreach ($lines as $i => $line) {
    if (strpos($line, $start_marker) !== false) {
        // We found it. Since the valid one is STEP 5, this must be the duplicate.
        // Adjust start index to include the decoration line before it
        $start_idx = $i - 1;
        break;
    }
}

if ($start_idx === -1) {
    die("Could not find start marker\n");
}

for ($i = $start_idx + 1; $i < count($lines); $i++) {
    if (strpos($lines[$i], $end_marker) !== false) {
        $end_idx = $i;
        break;
    }
}

if ($end_idx === -1) {
    die("Could not find end marker\n");
}

echo "Removing lines " . ($start_idx + 1) . " to " . $end_idx . "\n";

$new_lines = array_merge(
    array_slice($lines, 0, $start_idx),
    array_slice($lines, $end_idx)
);

file_put_contents($file_path, implode("", $new_lines));
echo "Successfully removed duplicate block.\n";
?>
