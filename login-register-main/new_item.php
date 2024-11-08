<?php
session_start();

require_once "database.php"; // Include your database connection

// Handle form submission
if (isset($_POST['add_new_item'])) {
    $item_name = $_POST['item_name'];
    $quantity = $_POST['quantity'];
    $price = $_POST['price'];

    // Check if item already exists
    $check_sql = "SELECT * FROM stocks WHERE Name = ?";
    $check_stmt = mysqli_prepare($conn, $check_sql);
    mysqli_stmt_bind_param($check_stmt, "s", $item_name);
    mysqli_stmt_execute($check_stmt);
    $result = mysqli_stmt_get_result($check_stmt);

    if (mysqli_num_rows($result) > 0) {
        echo "<div class='alert alert-danger'>Item already exists in the stock!</div>";
    } else {
        $sql = "INSERT INTO stocks (Name, Quantity, Price) VALUES (?, ?, ?)";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "sid", $item_name, $quantity, $price);
        
        if (mysqli_stmt_execute($stmt)) {
            echo "<div class='alert alert-success'>New item added successfully!</div>";
        } else {
            echo "<div class='alert alert-danger'>Error adding item: " . mysqli_error($conn) . "</div>";
        }

        mysqli_stmt_close($stmt);
    }
    mysqli_stmt_close($check_stmt);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.2/dist/css/bootstrap.min.css" integrity="sha384-Zenh87qX5JnK2Jl0vWa8Ck2rdkQ2Bzep5IDxbcnCeuOxjzrPF/et3URy9Bv1WTRi" crossorigin="anonymous">
    <link rel="stylesheet" href="style.css">
    <title>Add New Item</title>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <span class="navbar-text me-3 text-light">
                Welcome, <?php echo htmlspecialchars($_SESSION["user"]["full_name"]); ?>
            </span>
            <a class="navbar-brand" href="admin_dashboard.php">Dnet Gaming Cafe</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link" href="new_item.php">Add New Item</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="update_stock.php">Update Stock</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="remove_item.php">Remove Item</a>
                    </li>
                    <?php if ($_SESSION["user"]["role"] != "user"): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="report.php">Report</a>
                    </li>
                    <?php endif; ?>
                </ul>
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-5">
        <h1>Add New Item to Stock</h1>
        <form action="new_item.php" method="post">
            <div class="form-group">
                <label for="item_name">Item Name</label>
                <input type="text" name="item_name" id="item_name" class="form-control" required>
            </div>
            <div class="form-group mt-3">
                <label for="quantity">Quantity</label>
                <input type="number" name="quantity" id="quantity" class="form-control" required min="1">
            </div>
            <div class="form-group mt-3">
                <label for="price">Price</label>
                <input type="number" name="price" id="price" class="form-control" required min="0" step="0.01">
            </div>
            <div class="form-btn mt-3">
                <input type="submit" value="Add New Item" name="add_new_item" class="btn btn-primary">
            </div>
        </form>
    </div>
</body>
</html>
