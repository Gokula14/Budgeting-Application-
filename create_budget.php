<?php
session_start();

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $title = $_POST['title'];
    $total_amount = floatval($_POST['total_amount']);
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];

    // Predefined categories
    $categories = [
        'Food',
        'Transportation',
        'Entertainment',
        'Utilities',
        'Savings'
    ];

    $category_amounts = [];
    $total_category_amount = 0;

    // Collect category amounts from the form submission
    foreach ($categories as $category) {
        $amount = floatval($_POST[$category]);
        $category_amounts[$category] = $amount;
        $total_category_amount += $amount;
    }

    // Validation: Ensure the total category amounts do not exceed the total budget
    if ($total_category_amount > $total_amount) {
        $error = "❌ The total of category amounts exceeds the total budget!";
    } else {
        // Insert into the budgets table (this is just an example, you need to have the appropriate table set up)
        $conn = new mysqli("localhost", "root", "", "budget_app");
        if ($conn->connect_error) {
            die("❌ Connection failed: " . $conn->connect_error);
        }

        $stmt = $conn->prepare("INSERT INTO budgets (user_id, title, amount, start_date, end_date) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("isdss", $_SESSION['user_id'], $title, $total_amount, $start_date, $end_date);
        
        if ($stmt->execute()) {
            // After creating the budget, you can insert the category budgets
            $budget_id = $stmt->insert_id;
            foreach ($category_amounts as $category => $amount) {
                $stmt = $conn->prepare("INSERT INTO category_budget (budget_id, category, amount) VALUES (?, ?, ?)");
                $stmt->bind_param("isd", $budget_id, $category, $amount);
                $stmt->execute();
            }
            header("Location: dashboard.php");
            exit();
        } else {
            $error = "❌ Failed to create budget: " . $stmt->error;
        }

        $conn->close();
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Create Budget</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script>
        function updateCategoryAmounts() {
            const totalAmount = parseFloat(document.getElementById('total_amount').value);
            let totalCategoryAmount = 0;

            // Loop over category inputs and sum the values
            const categoryInputs = document.querySelectorAll('.category-amount');
            categoryInputs.forEach(input => {
                totalCategoryAmount += parseFloat(input.value) || 0;
            });

            // Validate that the total category amount does not exceed the total budget
            if (totalCategoryAmount > totalAmount) {
                document.getElementById('error-message').innerText = '❌ The total of category amounts exceeds the total budget!';
            } else {
                document.getElementById('error-message').innerText = '';
            }
        }
    </script>
</head>
<body>

<div class="container mt-5">
    <h3>Create Budget</h3>

    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>

    <form method="POST" oninput="updateCategoryAmounts()">
        <div class="mb-3">
            <label for="title" class="form-label">Budget Title</label>
            <input type="text" name="title" class="form-control" required>
        </div>

        <div class="mb-3">
            <label for="total_amount" class="form-label">Total Budget Amount</label>
            <input type="number" id="total_amount" name="total_amount" class="form-control" required>
        </div>

        <!-- Display categories dynamically (hardcoded here) -->
        <div class="mb-3">
            <label class="form-label">Categories</label>
            <div class="row">
                <?php
                // Hardcoded categories list
                $categories = ['Food', 'Transportation', 'Entertainment', 'Utilities', 'Savings'];
                foreach ($categories as $category): ?>
                    <div class="col-md-6">
                        <label for="<?= $category ?>" class="form-label"><?= $category ?></label>
                        <input type="number" name="<?= $category ?>" class="form-control category-amount" value="0" required>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div id="error-message" class="text-danger mb-3"></div>

        <div class="mb-3">
            <label for="start_date" class="form-label">Start Date</label>
            <input type="date" name="start_date" class="form-control" required>
        </div>

        <div class="mb-3">
            <label for="end_date" class="form-label">End Date</label>
            <input type="date" name="end_date" class="form-control" required>
        </div>

        <button type="submit" class="btn btn-primary">Create Budget</button>
    </form>
</div>

</body>
</html>
