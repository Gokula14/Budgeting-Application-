<?php
session_start();

// Redirect to login if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Connect to the database
$conn = new mysqli("localhost", "root", "", "budget_app");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch all budgets for the logged-in user
$query = "SELECT id, title, amount, warning_triggered FROM budgets WHERE user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();

// Fetch the selected budget details if a budget_id is provided
$budget = null;
$categories = [];
if (isset($_GET['budget_id'])) {
    $budget_id = $_GET['budget_id'];

    // Fetch the budget data
    $budget_query = "SELECT id, title, amount, warning_triggered FROM budgets WHERE id = ? AND user_id = ?";
    $stmt = $conn->prepare($budget_query);
    $stmt->bind_param("ii", $budget_id, $_SESSION['user_id']);
    $stmt->execute();
    $budget_result = $stmt->get_result();

    if ($budget_result->num_rows > 0) {
        $budget = $budget_result->fetch_assoc();
    } else {
        echo "Budget not found.";
        exit();
    }

    // Fetch category-specific amounts for the selected budget
    $categories_query = "SELECT id, category, amount FROM category_budget WHERE budget_id = ?";
    $stmt = $conn->prepare($categories_query);
    $stmt->bind_param("i", $budget_id);
    $stmt->execute();
    $categories_result = $stmt->get_result();
    while ($category = $categories_result->fetch_assoc()) {
        $categories[] = $category;
    }
}

// Handle the form submission for updating the budget and categories
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = $_POST['title'];
    $amount = $_POST['amount'];
    $warning_triggered = isset($_POST['warning_triggered']) ? 1 : 0; // For checkbox input, 1 for checked

    // Update the budget in the database
    $update_query = "UPDATE budgets SET title = ?, amount = ?, warning_triggered = ? WHERE id = ? AND user_id = ?";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("sdiii", $title, $amount, $warning_triggered, $budget_id, $_SESSION['user_id']);
    
    if ($stmt->execute()) {
        // Update each category's amount
        foreach ($_POST['categories'] as $category_id => $category_amount) {
            $category_amount = floatval($category_amount); // Ensure the amount is a float
            $update_category_query = "UPDATE category_budget SET amount = ? WHERE id = ? AND budget_id = ?";
            $stmt = $conn->prepare($update_category_query);
            $stmt->bind_param("dii", $category_amount, $category_id, $budget_id);
            $stmt->execute();
        }

        header('Location: edit_budget.php'); // Redirect back to the edit_budget page after update
        exit();
    } else {
        echo "Error updating the budget.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Edit Budget - Budget App</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <style>
        body, html {
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', sans-serif;
        }
        .navbar {
            background-color: #6c63ff;
        }
        .navbar-brand, .nav-link {
            color: #fff !important;
        }
        .form-container {
            padding: 60px 20px;
            max-width: 800px;
            margin: 0 auto;
            background: linear-gradient(135deg, #eceef1, #f6f9fc);
            border-radius: 1rem;
        }
        .form-container h2 {
            color: #6c63ff;
        }
        .form-container .btn {
            background-color: #6c63ff;
            color: white;
        }
        .back-btn {
            margin-top: 80px;
            margin-bottom: 20px;
            display: block;
            width: 200px;
            text-align: center;
            background-color: #6c63ff;
            color: white;
            border: none;
            padding: 10px;
            border-radius: 5px;
            text-decoration: none;
        }
        .back-btn:hover {
            background-color: #5a53e6;
        }
    </style>
</head>
<body>

    <!-- Top Navbar -->
    <nav class="navbar navbar-expand-lg fixed-top">
        <div class="container-fluid justify-content-center">
            <a class="navbar-brand fw-bold text-center" href="#">BudgetApp</a>
        </div>
    </nav>

    <!-- Back to Dashboard Button -->
    <a href="dashboard.php" class="back-btn">Back to Dashboard</a>

    <!-- List of Budgets -->
    <div class="container mt-5">
        <h2>Your Budgets</h2>
        <?php if ($result->num_rows > 0): ?>
            <div class="list-group">
                <?php while ($budget_item = $result->fetch_assoc()): ?>
                    <a href="edit_budget.php?budget_id=<?php echo $budget_item['id']; ?>" class="list-group-item list-group-item-action">
                        <div class="d-flex justify-content-between">
                            <h5 class="mb-1"><?php echo htmlspecialchars($budget_item['title']); ?></h5>
                            <small>₹<?php echo number_format($budget_item['amount'], 2); ?></small>
                        </div>
                        <p class="mb-1">Warning Triggered: <?php echo $budget_item['warning_triggered'] ? 'Yes' : 'No'; ?></p>
                    </a>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <p>No budgets found.</p>
        <?php endif; ?>
    </div>

    <!-- Edit Budget Form (only show if a budget is selected) -->
    <?php if ($budget): ?>
        <div class="form-container mt-5">
            <h2>Edit Budget</h2>
            <form action="edit_budget.php?budget_id=<?php echo $budget['id']; ?>" method="POST">
                <!-- Budget Information -->
                <div class="mb-3">
                    <label for="title" class="form-label">Budget Title</label>
                    <input type="text" class="form-control" id="title" name="title" value="<?php echo htmlspecialchars($budget['title']); ?>" required>
                </div>
                <div class="mb-3">
                    <label for="amount" class="form-label">Total Budget Amount (₹)</label>
                    <input type="number" class="form-control" id="amount" name="amount" value="<?php echo htmlspecialchars($budget['amount']); ?>" step="0.01" required>
                </div>
                <div class="mb-3 form-check">
                    <input type="checkbox" class="form-check-input" id="warning_triggered" name="warning_triggered" <?php echo $budget['warning_triggered'] ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="warning_triggered">Warning Triggered</label>
                </div>

                <!-- Category Information -->
                <h4>Categories</h4>
                <?php foreach ($categories as $category): ?>
                    <div class="mb-3">
                        <label for="category_<?php echo $category['id']; ?>" class="form-label"><?php echo htmlspecialchars($category['category']); ?> (₹)</label>
                        <input type="number" class="form-control" id="category_<?php echo $category['id']; ?>" name="categories[<?php echo $category['id']; ?>]" value="<?php echo htmlspecialchars($category['amount']); ?>" step="0.01" required>
                    </div>
                <?php endforeach; ?>

                <button type="submit" class="btn btn-primary">Update Budget</button>
            </form>
        </div>
    <?php endif; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
