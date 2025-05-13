<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$username = $_SESSION['username'];

$conn = new mysqli("localhost", "root", "", "budget_app");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$latest_budget_query = "SELECT id, title, amount, warning_triggered FROM budgets WHERE user_id = ? ORDER BY created_at DESC LIMIT 1";
$stmt = $conn->prepare($latest_budget_query);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$latest_budget_result = $stmt->get_result();
$latest_budget = $latest_budget_result->fetch_assoc();

$total_expenses = 0;
$expenses_query = "SELECT amount FROM expenses WHERE budget_id = ?";
$stmt = $conn->prepare($expenses_query);
$stmt->bind_param("i", $latest_budget['id']);
$stmt->execute();
$expenses_result = $stmt->get_result();
while ($expense = $expenses_result->fetch_assoc()) {
    $total_expenses += $expense['amount'];
}

$balance = $latest_budget['amount'] - $total_expenses;

$warning_query = "SELECT e.warning_message, cb.category AS category_name 
                  FROM expenses e
                  JOIN category_budget cb ON e.category_id = cb.id
                  WHERE e.budget_id = ? AND e.warning_message IS NOT NULL
                  ORDER BY e.date DESC";

$stmt = $conn->prepare($warning_query);
$stmt->bind_param("i", $latest_budget['id']);
$stmt->execute();
$warning_result = $stmt->get_result();

$warnings = [];
while ($row = $warning_result->fetch_assoc()) {
    $warnings[] = $row['category_name'] . ": " . $row['warning_message'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Dashboard - Budget App</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet" />
  <style>
    body, html {
      margin: 0;
      padding: 0;
      height: 100%;
      font-family: 'Segoe UI', sans-serif;
    }

    .navbar {
      background-color: #6c63ff;
    }

    .navbar-brand, .nav-link {
      color: #fff !important;
    }

    .dashboard {
      padding: 80px 20px 40px;
      min-height: 100vh;
      background: linear-gradient(135deg, #eceef1, #f6f9fc);
      overflow-y: auto;
    }

    .card-box {
      border-radius: 1rem;
      box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05);
      padding: 20px;
      background: white;
      height: 100%;
    }

    .card-title {
      font-size: 0.95rem;
      font-weight: 600;
      color: #6c63ff;
    }

    .card-value {
      font-size: 1.5rem;
      font-weight: bold;
      color: #333;
    }

    .action-buttons .btn {
      margin: 10px;
      border-radius: 0.5rem;
      min-width: 180px;
    }

    .btn-primary-bg {
      background-color: #6c63ff;
      color: white;
    }

    .btn-success-bg {
      background-color: #28a745;
      color: white;
    }

    .btn-outline-primary-bg {
      background-color: #007bff;
      color: white;
      border: none;
    }

    .section-header {
      font-weight: bold;
      font-size: 1.3rem;
      margin-top: 50px;
      margin-bottom: 20px;
      color: #333;
    }

    .alert-warning {
      background-color: #f8d7da;
      color: #721c24;
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

  <!-- Scrollable Dashboard -->
  <div class="dashboard container-fluid">
    <div class="container">
      <h2 class="fw-bold">Welcome, <?php echo htmlspecialchars($username); ?>!</h2>
      <p class="text-muted">Manage your budgets, expenses, and summaries</p>
<div>
      <a href="create_budget.php" class="btn btn-primary-bg mt-3 mb-4" style="margin: 10px 15px;">+ Create New Budget</a>
<a href="edit_budget.php" class="btn btn-primary-bg mt-3 mb-4" style="margin: 10px 15px;">Edit Budget</a>
<a href="delete_budget.php" class="btn btn-primary-bg mt-3 mb-4" style="margin: 10px 15px;">- Delete Budget</a>
<a href="expense_tracker.php" class="btn btn-success-bg mt-3 mb-4" style="margin: 10px 15px;">+ Add Expense</a>
<a href="category.php" class="btn btn-outline-primary-bg mt-3 mb-4" style="margin: 10px 15px;">Categories</a>
<a href="expense_screen.php" class="btn btn-outline-primary-bg mt-3 mb-4" style="margin: 10px 15px;">Expense Tracker</a>
  </div>


      <!-- Budget Cards -->
      <div class="row g-4">
        <div class="col-md-4">
          <div class="card-box">
            <div class="card-title">Total Budget</div>
            <div class="card-value">₹<?php echo number_format($latest_budget['amount'], 2); ?></div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="card-box">
            <div class="card-title">Total Spent</div>
            <div class="card-value">₹<?php echo number_format($total_expenses, 2); ?></div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="card-box">
            <div class="card-title">Balance</div>
            <div class="card-value">₹<?php echo number_format($balance, 2); ?></div>
          </div>
        </div>
      </div>

      <!-- Warning Alert for Over Budget -->
      <?php if ($balance < 0): ?>
        <div class="alert alert-warning mt-4">
          Warning: Your expenses have exceeded your budget!
        </div>
      <?php endif; ?>

      <!-- Display category-specific warnings -->
      <?php if (!empty($warnings)): ?>
        <div class="alert alert-warning mt-4">
          <?php foreach ($warnings as $warning): ?>
            <p><?php echo htmlspecialchars($warning); ?></p>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
