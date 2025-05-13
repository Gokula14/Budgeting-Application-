<?php
// fetch_categories.php

// Check if budget_id is provided
if (isset($_GET['budget_id'])) {
    $budget_id = $_GET['budget_id'];

    // Connect to the database
    $conn = new mysqli("localhost", "root", "", "budget_app");

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Fetch categories for the selected budget
    $categories_query = "SELECT id, category FROM category_budget WHERE budget_id = ?";
    $stmt = $conn->prepare($categories_query);
    $stmt->bind_param("i", $budget_id);
    $stmt->execute();
    $categories_result = $stmt->get_result();

    // Output categories as options
    while ($row = $categories_result->fetch_assoc()) {
        echo "<option value='{$row['id']}'>{$row['category']}</option>";
    }

    $stmt->close();
    $conn->close();
}
?>
