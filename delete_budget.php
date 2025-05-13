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

// Handle the delete request
if (isset($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];

    // First, get category_budget IDs for this budget
    $get_categories_query = "SELECT id FROM category_budget WHERE budget_id = ?";
    $stmt = $conn->prepare($get_categories_query);
    $stmt->bind_param("i", $delete_id);
    $stmt->execute();
    $categories_result = $stmt->get_result();

    $category_ids = [];
    while ($row = $categories_result->fetch_assoc()) {
        $category_ids[] = $row['id'];
    }

    // Delete expenses linked to these category_budget IDs
    if (!empty($category_ids)) {
        $placeholders = implode(',', array_fill(0, count($category_ids), '?'));
        $types = str_repeat('i', count($category_ids));
        $stmt = $conn->prepare("DELETE FROM expenses WHERE category_id IN ($placeholders)");
        $stmt->bind_param($types, ...$category_ids);
        $stmt->execute();
    }

    // Delete from category_budget
    $stmt = $conn->prepare("DELETE FROM category_budget WHERE budget_id = ?");
    $stmt->bind_param("i", $delete_id);
    $stmt->execute();

    // Delete from budgets
    $stmt = $conn->prepare("DELETE FROM budgets WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $delete_id, $_SESSION['user_id']);
    
    if ($stmt->execute()) {
        header('Location: delete_budget.php');
        exit();
    } else {
        echo "Error deleting the budget.";
    }
}



// Fetch all budgets for the logged-in user
$query = "SELECT id, title, amount FROM budgets WHERE user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Delete Budget - Budget App</title>
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
                    <div class="list-group-item d-flex justify-content-between">
                        <div>
                            <h5 class="mb-1"><?php echo htmlspecialchars($budget_item['title']); ?></h5>
                            <p class="mb-1">Amount: ₹<?php echo number_format($budget_item['amount'], 2); ?></p>
                        </div>
                        <div>
                            <a href="delete_budget.php?delete_id=<?php echo $budget_item['id']; ?>" class="btn btn-danger btn-sm">Delete</a>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <p>No budgets found.</p>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
