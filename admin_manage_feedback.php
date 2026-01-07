<?php
session_start();
// Ideally add admin check here: if(!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') ...
require_once 'backend/config.php';

$conn = getDatabaseConnection();
$message = '';

// Handle Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'add') {
            $text = $_POST['question_text'];
            $type = $_POST['question_type'];
            $options = !empty($_POST['options_json']) ? $_POST['options_json'] : null;
            
            $stmt = $conn->prepare("INSERT INTO feedback_questions (question_text, question_type, options_json) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $text, $type, $options);
            if ($stmt->execute()) $message = "Question added successfully.";
            else $message = "Error adding question: " . $conn->error;
            
        } elseif ($_POST['action'] === 'delete') {
            $id = $_POST['id'];
            $stmt = $conn->prepare("DELETE FROM feedback_questions WHERE id = ?");
            $stmt->bind_param("i", $id);
            if ($stmt->execute()) $message = "Question deleted.";
            
        } elseif ($_POST['action'] === 'toggle') {
            $id = $_POST['id'];
            $current = $_POST['current_status'];
            $new = ($current == 1) ? 0 : 1;
            $stmt = $conn->prepare("UPDATE feedback_questions SET is_active = ? WHERE id = ?");
            $stmt->bind_param("ii", $new, $id);
            $stmt->execute();
        }
    }
}

// Fetch Questions
$questions = $conn->query("SELECT * FROM feedback_questions ORDER BY id ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Feedback Questions - Constructa Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --primary: #294033; --bg: #f0f2f5; }
        body { font-family: 'Inter', sans-serif; background: var(--bg); padding: 2rem; }
        .container { max-width: 1000px; margin: 0 auto; background: white; padding: 2rem; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.05); }
        h1 { color: var(--primary); margin-bottom: 2rem; }
        table { width: 100%; border-collapse: collapse; margin-top: 2rem; }
        th, td { padding: 1rem; text-align: left; border-bottom: 1px solid #eee; }
        th { background: #f9f9f9; font-weight: 600; }
        .badge { padding: 4px 8px; border-radius: 4px; font-size: 0.85rem; }
        .badge-active { background: #dcfce7; color: #166534; }
        .badge-inactive { background: #fef2f2; color: #991b1b; }
        .btn { padding: 8px 12px; border: none; border-radius: 6px; cursor: pointer; font-size: 0.9rem; }
        .btn-primary { background: var(--primary); color: white; }
        .btn-danger { background: #fee2e2; color: #991b1b; }
        .form-box { background: #f8fafc; padding: 1.5rem; border-radius: 8px; margin-bottom: 2rem; border: 1px solid #e2e8f0; }
        .form-row { display: flex; gap: 1rem; margin-bottom: 1rem; }
        input, select { padding: 8px; border: 1px solid #ccc; border-radius: 4px; flex: 1; }
    </style>
</head>
<body>
    <div class="container">
        <h1><i class="fas fa-tasks"></i> Feedback Configuration</h1>
        
        <?php if($message): ?>
            <div style="padding:1rem; background:#dcfce7; color:#166534; margin-bottom:1rem; border-radius:6px;">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <div class="form-box">
            <h3>Add New Question</h3>
            <form method="POST">
                <input type="hidden" name="action" value="add">
                <div class="form-row">
                    <input type="text" name="question_text" placeholder="Question Text" required style="flex:3">
                    <select name="question_type" style="flex:1">
                        <option value="text">Text Input</option>
                        <option value="textarea">Text Area</option>
                        <option value="rating">Star Rating</option>
                        <option value="select">Dropdown</option>
                    </select>
                </div>
                <div class="form-row">
                    <input type="text" name="options_json" placeholder='Options JSON (e.g. ["Yes","No"]) for Dropdown'>
                    <button type="submit" class="btn btn-primary">Add Question</button>
                </div>
            </form>
        </div>

        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Question</th>
                    <th>Type</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if($questions): while($row = $questions->fetch_assoc()): ?>
                <tr>
                    <td>#<?php echo $row['id']; ?></td>
                    <td><?php echo htmlspecialchars($row['question_text']); ?></td>
                    <td><code><?php echo $row['question_type']; ?></code></td>
                    <td>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="action" value="toggle">
                            <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                            <input type="hidden" name="current_status" value="<?php echo $row['is_active']; ?>">
                            <button type="submit" class="btn <?php echo $row['is_active'] ? 'badge-active' : 'badge-inactive'; ?>">
                                <?php echo $row['is_active'] ? 'Active' : 'Inactive'; ?>
                            </button>
                        </form>
                    </td>
                    <td>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                            <button type="submit" class="btn btn-danger" onclick="return confirm('Delete this question?')"><i class="fas fa-trash"></i></button>
                        </form>
                    </td>
                </tr>
                <?php endwhile; endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
