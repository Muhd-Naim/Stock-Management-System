<?php
session_start();

require_once "database.php"; // Include your database connection

// Handle form submission
if (isset($_POST['remove_item'])) {
    $item_name = $_POST['item_name'];

    // Delete the selected item from the stocks table
    $sql = "DELETE FROM stocks WHERE Name = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "s", $item_name);
    
    if (mysqli_stmt_execute($stmt)) {
        echo "<div class='alert alert-success'>Item '$item_name' removed successfully!</div>";
    } else {
        echo "<div class='alert alert-danger'>Error removing item: " . mysqli_error($conn) . "</div>";
    }

    mysqli_stmt_close($stmt);
}

// Fetch items for the dropdown
$sql = "SELECT Name FROM stocks";
$result = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.2/dist/css/bootstrap.min.css" integrity="sha384-Zenh87qX5JnK2Jl0vWa8Ck2rdkQ2Bzep5IDxbcnCeuOxjzrPF/et3URy9Bv1WTRi" crossorigin="anonymous">
    <link rel="stylesheet" href="style.css">
    <title>Remove Item</title>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <span class="navbar-text me-3 text-light">
                Welcome, <?php echo htmlspecialchars($_SESSION["user"]["full_name"]); ?>
            </span>
            <a class="navbar-brand" href="admin_dashboard.php">Dnet Gaming Cafe</a>
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
        <h1>Remove Item from Stock</h1>
        <form action="remove_item.php" method="post">
            <div class="form-group">
                <label for="item_name">Item Name</label>
                <select name="item_name" id="item_name" class="form-control" required>
                    <option value="">Select an Item</option>
                    <?php
                    // Populate dropdown with item names
                    while ($row = mysqli_fetch_assoc($result)) {
                        echo "<option value='" . htmlspecialchars($row['Name']) . "'>" . htmlspecialchars($row['Name']) . "</option>";
                    }
                    ?>
                </select>
            </div>
            <div class="form-btn mt-3">
                <input type="submit" value="Remove Item" name="remove_item" class="btn btn-danger">
            </div>
        </form>
    </div>
</body>
</html>
