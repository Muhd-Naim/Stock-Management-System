<?php
session_start();
require_once "database.php"; // Include your database connection

// Check if user is logged in and if they have appropriate role to update stock
if (!isset($_SESSION["user"]) || $_SESSION["user"]["role"] == 'guest') {
    header("Location: login.php");
    exit();
}

$sold_items = []; // Array to track sold items

// Handle form submission for updating stock
if (isset($_POST['update_stock'])) {
    $items = $_POST['items']; 
    $user_id = $_SESSION["user"]["id"];
    $full_name = $_SESSION["user"]["full_name"];

    if (isset($_POST['shift']) && !empty($_POST['shift'])) {
        $shift = $_POST['shift'];
    } else {
        echo "<div class='alert alert-danger'>Error: Shift is required.</div>";
        exit();
    }

    $total_amount = 0;

    // Create a new report entry in 'reports'
    $sql_insert_report = "INSERT INTO reports (full_name, shift, total_amount, report_date, user_id) VALUES (?, ?, ?, NOW(), ?)";
    $stmt_report = mysqli_prepare($conn, $sql_insert_report);
    mysqli_stmt_bind_param($stmt_report, "ssdi", $full_name, $shift, $total_amount, $user_id);
    
    if (!mysqli_stmt_execute($stmt_report)) {
        echo "<div class='alert alert-danger'>Error creating report: " . mysqli_error($conn) . "</div>";
        exit();
    }

    // Get the last inserted report ID
    $report_id = mysqli_insert_id($conn);

    // Loop through each item to update stock and report sold items
    foreach ($items as $item_name => $quantities) {
        $quantity_left = floatval($quantities['left'] ?? 0);
        $quantity_to_add = floatval($quantities['add'] ?? 0);

        $sql_current = "SELECT Quantity, Price FROM stocks WHERE Name = ?";
        $stmt_current = mysqli_prepare($conn, $sql_current);
        mysqli_stmt_bind_param($stmt_current, "s", $item_name);
        mysqli_stmt_execute($stmt_current);
        mysqli_stmt_bind_result($stmt_current, $current_quantity, $price);
        mysqli_stmt_fetch($stmt_current);
        mysqli_stmt_close($stmt_current);

        $updated_quantity = $current_quantity + $quantity_to_add;
        $quantity_sold = $updated_quantity - $quantity_left;

        // Ensure the quantity sold is not negative
        if ($quantity_sold < 0) {
            echo "<div class='alert alert-danger'>Error: Stock left for $item_name cannot be greater than available stock after adding new stock.</div>";
            continue;
        }

        // Update the stock and total sold
        $sql_update = "UPDATE stocks SET Quantity = ?, TotalSold = TotalSold + ? WHERE Name = ?";
        $stmt_update = mysqli_prepare($conn, $sql_update);
        mysqli_stmt_bind_param($stmt_update, "dis", $quantity_left, $quantity_sold, $item_name);
        if (mysqli_stmt_execute($stmt_update)) {
            if ($quantity_sold > 0) {
                $total_price = $quantity_sold * $price;
                $total_amount += $total_price;

                $sold_items[] = [
                    'name' => $item_name,
                    'quantity_sold' => $quantity_sold,
                    'price' => $price,
                    'total_price' => number_format($total_price, 2)
                ];
                
                $sql_insert_item = "INSERT INTO report_items (report_id, item_name, quantity, total_price, user_id) VALUES (?, ?, ?, ?, ?)";
                $stmt_item = mysqli_prepare($conn, $sql_insert_item);
                mysqli_stmt_bind_param($stmt_item, "isidi", $report_id, $item_name, $quantity_sold, $total_price, $user_id);
                if (!mysqli_stmt_execute($stmt_item)) {
                    echo "<div class='alert alert-danger'>Error saving report for $item_name: " . mysqli_error($conn) . "</div>";
                }
                mysqli_stmt_close($stmt_item);
            }
        } else {
            echo "<div class='alert alert-danger'>Error updating stock for $item_name: " . mysqli_error($conn) . "</div>";
        }
        mysqli_stmt_close($stmt_update);
    }

    // Update the total amount in the report
    $sql_update_report = "UPDATE reports SET total_amount = ? WHERE report_date = NOW() AND user_id = ?"; // Adjust the WHERE clause if you need a specific identifier
    $stmt_update_report = mysqli_prepare($conn, $sql_update_report);
    mysqli_stmt_bind_param($stmt_update_report, "di", $total_amount, $user_id);
    mysqli_stmt_execute($stmt_update_report);
    mysqli_stmt_close($stmt_update_report);

    // Display success message
    echo "<div class='alert alert-success'>Stock updated successfully!</div>";
}

// Fetch items for the table, including price and quantity
$sql_fetch = "SELECT Name, Quantity, TotalSold, Price FROM stocks";
$result = mysqli_query($conn, $sql_fetch);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="style.css">
    <title>Update Stock</title>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <span class="navbar-text me-3 text-light">
                Welcome, <?php echo htmlspecialchars($_SESSION["user"]["full_name"]); ?>
            </span>
            <a class="navbar-brand" href="user_dashboard.php">Dnet Gaming Cafe</a>
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

    <div class="container-fluid mt-5">
        <h1>Update Stock</h1>
        <form action="update_stock.php" method="post">
            <!-- Add a shift selection -->
            <div class="mb-3">
                <h2><label for="shift" class="form-label">Shift</label></h2>
                <select name="shift" id="shift" class="form-control" required>
                    <option value="">Select Shift</option>
                    <option value="Day">Day</option>
                    <option value="Midday">Midday</option>
                    <option value="Night">Night</option>
                </select>
            </div>
            
            <table class="table">
                <thead>
                    <tr>
                        <th>Item Name</th>
                        <th>Current Quantity</th>
                        <th>Price per Item</th>
                        <th>Add Stock</th>
                        <th>Stock Left</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    while ($row = mysqli_fetch_assoc($result)) {
                        echo "<tr>
                                <td>" . htmlspecialchars($row['Name']) . "</td>
                                <td>" . htmlspecialchars($row['Quantity']) . "</td>
                                <td>RM " . number_format($row['Price'], 2) . "</td>
                                <td><input type='number' name='items[" . $row['Name'] . "][add]' class='form-control' min='0'></td>
                                <td><input type='number' name='items[" . $row['Name'] . "][left]' class='form-control' required min='0'></td>
                            </tr>";
                    }
                    ?>
                </tbody>
            </table>
            <div class="mb-3">
                <input type="submit" value="Update Stock" name="update_stock" class="btn btn-primary">
            </div>
        </form>

        <!-- Show sold items table -->
        <?php if (!empty($sold_items)): ?>
            <h1 class="mt-5">Sold Items</h1>
            <table class="table">
                <thead>
                    <tr>
                        <th>Item Name</th>
                        <th>Quantity Sold</th>
                        <th>Price per Item</th>
                        <th>Total Price</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($sold_items as $sold_item): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($sold_item['name']); ?></td>
                            <td><?php echo htmlspecialchars($sold_item['quantity_sold']); ?></td>
                            <td>RM <?php echo number_format($sold_item['price'], 2); ?></td>
                            <td>RM <?php echo $sold_item['total_price']; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</body>
</html>
