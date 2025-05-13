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

// Fetch all budgets for the user
$budget_query = "SELECT id, title, amount FROM budgets WHERE user_id = ?";
$stmt = $conn->prepare($budget_query);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$budget_result = $stmt->get_result();

// If a budget is selected, fetch the categories and expenses
if (isset($_GET['budget_id'])) {
    $budget_id = $_GET['budget_id'];

    // Fetch the selected budget
    $selected_budget_query = "SELECT id, title, amount FROM budgets WHERE id = ?";
    $stmt = $conn->prepare($selected_budget_query);
    $stmt->bind_param("i", $budget_id);
    $stmt->execute();
    $selected_budget_result = $stmt->get_result();

    if ($selected_budget_result->num_rows > 0) {
        $selected_budget = $selected_budget_result->fetch_assoc();

        // Fetch categories and the amounts allocated and spent
        $category_query = "SELECT cb.category, cb.amount AS allocated_amount, SUM(e.amount) AS spent_amount
                           FROM category_budget cb
                           LEFT JOIN expenses e ON cb.id = e.category_id
                           WHERE cb.budget_id = ?
                           GROUP BY cb.id";
        $stmt = $conn->prepare($category_query);
        $stmt->bind_param("i", $budget_id);
        $stmt->execute();
        $category_result = $stmt->get_result();
    } else {
        // Redirect or display an error if the budget doesn't exist
        echo "Selected budget not found.";
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Categories - Budget App</title>
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

    .alert-warning {
      background-color: #f8d7da;
      color: #721c24;
    }

    .category-item {
      margin-top: 20px;
      padding: 10px;
      background-color: #fff;
      box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
      border-radius: 10px;
    }

    .category-item h5 {
      font-size: 1.2rem;
      font-weight: 600;
    }

    .category-item .amount {
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
      <p class="text-muted">Manage your categories and budgets</p>

      <!-- Budget List -->
      <h4 class="section-header">Your Budgets</h4>
      <div class="row g-4">
        <?php while ($budget = $budget_result->fetch_assoc()): ?>
          <div class="col-md-4">
            <div class="card-box">
              <div class="card-title"><?php echo htmlspecialchars($budget['title']); ?></div>
              <div class="card-value">₹<?php echo number_format($budget['amount'], 2); ?></div>
              <a href="category.php?budget_id=<?php echo $budget['id']; ?>" class="btn btn-outline-primary mt-3">View Categories</a>
            </div>
          </div>
        <?php endwhile; ?>
      </div>

      <?php if (isset($selected_budget)): ?>
        <!-- Categories for selected budget -->
        <h4 class="section-header">Categories in "<?php echo htmlspecialchars($selected_budget['title']); ?>"</h4>
        <div class="row g-4">
          <?php while ($category = $category_result->fetch_assoc()): ?>
            <div class="col-md-4">
              <div class="category-item">
                <h5><?php echo htmlspecialchars($category['category']); ?></h5>
                <div class="amount">
                  <p>Allocated: ₹<?php echo number_format($category['allocated_amount'], 2); ?></p>
                  <p>Spent: ₹<?php echo number_format($category['spent_amount'], 2); ?></p>
                </div>
              </div>
            </div>
          <?php endwhile; ?>
        </div>
      <?php else: ?>
        <div class="alert alert-warning" role="alert">
          No categories available for this budget.
        </div>
      <?php endif; ?>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
