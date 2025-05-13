<?php
session_start();

// Redirect to login if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$username = $_SESSION['username'];

// Connect to the database
$conn = new mysqli("localhost", "root", "", "budget_app");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch the user's budget (including the title column)
$budget_query = "
    SELECT b.id, b.title, b.amount AS allocated_amount
    FROM budgets b
    WHERE b.user_id = ?";
$stmt = $conn->prepare($budget_query);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$budget_result = $stmt->get_result();

// Fetch daily expenses for the budget
$expense_query = "
    SELECT SUM(e.amount) AS total_spent, DATE(e.date) AS expense_date
    FROM expenses e
    JOIN budgets b ON e.budget_id = b.id
    WHERE b.user_id = ?
    GROUP BY DATE(e.date)";
$stmt = $conn->prepare($expense_query);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$expense_result = $stmt->get_result();

// Store total spent by date
$total_spent_by_date = [];
while ($expense = $expense_result->fetch_assoc()) {
    $total_spent_by_date[$expense['expense_date']] = $expense['total_spent'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Expense Screen - Budget App</title>
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
      background-color: #6c63ff;
      color: white;
      margin: 10px;
      border-radius: 0.5rem;
      min-width: 180px;
    }

    .section-header {
      font-weight: bold;
      font-size: 1.3rem;
      margin-top: 50px;
      margin-bottom: 20px;
      color: #333;
    }

    .budget-item {
      margin-top: 20px;
      padding: 10px;
      background-color: #fff;
      box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
      border-radius: 10px;
    }

    .budget-item h5 {
      font-size: 1.2rem;
      font-weight: 600;
    }

    .budget-item .amount {
      font-size: 1rem;
      color: #333;
    }

    .back-btn {
      background-color: #6c63ff;
      color: white;
      margin-left: 20px;
      border-radius: 0.5rem;
    }
  </style>
</head>
<body>

  <!-- Top Navbar -->
  <nav class="navbar navbar-expand-lg fixed-top">
    <div class="container-fluid justify-content-between">
      <a class="navbar-brand fw-bold text-center" href="#">BudgetApp</a>
      <a href="dashboard.php" class="btn back-btn">Back to Dashboard</a>
    </div>
  </nav>

  <!-- Scrollable Dashboard -->
  <div class="dashboard container-fluid">
    <div class="container">
      <h2 class="fw-bold">Welcome, <?php echo htmlspecialchars($username); ?>!</h2>
      <p class="text-muted">View your budget and expenses</p>

      <!-- Budget and Expenses -->
      <h4 class="section-header">Budget Overview</h4>
      <div class="row g-4">
        <?php while ($budget = $budget_result->fetch_assoc()): ?>
          <div class="col-md-4">
            <div class="budget-item">
              <h5><?php echo htmlspecialchars($budget['title']); ?></h5> <!-- Displaying the budget title -->
              <div class="amount">
                <p>Allocated: ₹<?php echo number_format($budget['allocated_amount'], 2); ?></p>

                <?php 
                $total_spent = 0;
                foreach ($total_spent_by_date as $date => $spent) {
                    $total_spent += $spent;
                ?>
                    <p>Spent on <?php echo $date; ?>: ₹<?php echo number_format($spent, 2); ?></p>
                <?php
                }
                ?>

                <p>Total Spent: ₹<?php echo number_format($total_spent, 2); ?></p>
                <p>Remaining Balance: ₹<?php echo number_format($budget['allocated_amount'] - $total_spent, 2); ?></p>
              </div>
            </div>
          </div>
        <?php endwhile; ?>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
