<?php
session_start();

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Connect to the database
$conn = new mysqli("localhost", "root", "", "budget_app");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch available budgets for the dropdown
$budgets_query = "SELECT id, title FROM budgets WHERE user_id = ?";
$stmt = $conn->prepare($budgets_query);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$budgets_result = $stmt->get_result();

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $budget_id = $_POST['budget_id'];
    $category_id = $_POST['category_id'];
    $amount = $_POST['amount'];

    // Get the budget for the category
    $category_budget_query = "SELECT amount FROM category_budget WHERE budget_id = ? AND id = ?";
    $stmt = $conn->prepare($category_budget_query);
    $stmt->bind_param("ii", $budget_id, $category_id);
    $stmt->execute();
    $category_budget_result = $stmt->get_result();
    $category_budget = $category_budget_result->fetch_assoc()['amount'];

    // Get the total expense for the category
    $total_expense_query = "SELECT SUM(amount) AS total_expense FROM expenses WHERE budget_id = ? AND category_id = ?";
    $stmt = $conn->prepare($total_expense_query);
    $stmt->bind_param("ii", $budget_id, $category_id);
    $stmt->execute();
    $total_expense_result = $stmt->get_result();
    $total_expense = $total_expense_result->fetch_assoc()['total_expense'];

    // Check if the new expense exceeds the budget
    $warning_message = null;
    if ($total_expense + $amount > $category_budget) {
        $warning_message = "Warning: Expense for this category has exceeded the budget!";
    }

    // Insert the expense into the database
    $expense_query = "INSERT INTO expenses (budget_id, category_id, amount, warning_message) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($expense_query);
    $stmt->bind_param("iiis", $budget_id, $category_id, $amount, $warning_message);

    if ($stmt->execute()) {
        $success_message = "Expense recorded successfully!";
    } else {
        $error_message = "Failed to record the expense!";
    }
}

// Fetch categories based on the selected budget
if (isset($_POST['budget_id']) || isset($_GET['budget_id'])) {
    $budget_id = isset($_POST['budget_id']) ? $_POST['budget_id'] : $_GET['budget_id'];
    $categories_query = "SELECT id, category FROM category_budget WHERE budget_id = ?";
    $stmt = $conn->prepare($categories_query);
    $stmt->bind_param("i", $budget_id);
    $stmt->execute();
    $categories_result = $stmt->get_result();
} else {
    $categories_result = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Expense Tracker</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script>
        function fetchCategories() {
            var budget_id = document.getElementById('budget_id').value;
            if (budget_id) {
                var xhr = new XMLHttpRequest();
                xhr.open("GET", "fetch_categories.php?budget_id=" + budget_id, true);
                xhr.onload = function() {
                    if (xhr.status === 200) {
                        document.getElementById('category_id').innerHTML = xhr.responseText;
                    }
                };
                xhr.send();
            }
        }
    </script>
</head>
<body>

<div class="container mt-5">
    <h2 class="text-center">Expense Tracker</h2>

    <!-- Display Success or Error Message -->
    <?php if (isset($success_message)): ?>
        <div class="alert alert-success"><?php echo $success_message; ?></div>
    <?php elseif (isset($error_message)): ?>
        <div class="alert alert-danger"><?php echo $error_message; ?></div>
    <?php elseif (isset($warning_message)): ?>
        <div class="alert alert-warning"><?php echo $warning_message; ?></div>
    <?php endif; ?>

    <!-- Expense Form -->
    <form method="POST" action="">
        <div class="mb-3">
            <label for="budget_id" class="form-label">Select Budget</label>
            <select class="form-select" id="budget_id" name="budget_id" required onchange="fetchCategories()">
                <option value="">Select Budget</option>
                <?php while ($row = $budgets_result->fetch_assoc()): ?>
                    <option value="<?php echo $row['id']; ?>" <?php echo (isset($budget_id) && $budget_id == $row['id']) ? 'selected' : ''; ?>>
                        <?php echo $row['title']; ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>

        <div class="mb-3">
            <label for="category_id" class="form-label">Select Category</label>
            <select class="form-select" id="category_id" name="category_id" required>
                <option value="">Select Category</option>
                <?php if (!empty($categories_result)): ?>
                    <?php while ($row = $categories_result->fetch_assoc()): ?>
                        <option value="<?php echo $row['id']; ?>"><?php echo $row['category']; ?></option>
                    <?php endwhile; ?>
                <?php endif; ?>
            </select>
        </div>

        <div class="mb-3">
            <label for="amount" class="form-label">Amount</label>
            <input type="number" class="form-control" id="amount" name="amount" required>
        </div>

        <button type="submit" class="btn btn-primary">Add Expense</button>
    </form>

    <!-- Back to Dashboard Button -->
    <div class="mt-3">
        <a href="dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
