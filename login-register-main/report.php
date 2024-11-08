<?php
session_start();
require_once "database.php"; // Include your database connection

// Check if the user is logged in
if (!isset($_SESSION["user"])) {
    header("Location: login.php");
    exit();
}

// Get the selected date from the form submission, or default to today's date
$selected_date = isset($_POST['selected_date']) ? $_POST['selected_date'] : date('Y-m-d');

// Handle form submission for generating a report
if (isset($_POST['sold_items'])) {
    $sold_items = json_decode($_POST['sold_items'], true);
    $full_name = $_SESSION['user']['full_name']; // Get full name from session
    $shift = $_POST['shift'];

    // Calculate the total amount from sold items
    $total_amount = 0;
    foreach ($sold_items as $item) {
        $total_amount += $item['quantity_sold'] * $item['price'];
    }

    // Insert report data into the reports table
    $sql_insert = "INSERT INTO reports (full_name, shift, total_amount, report_date, user_id) VALUES (?, ?, ?, NOW(), ?)";
    $stmt_insert = mysqli_prepare($conn, $sql_insert);
    mysqli_stmt_bind_param($stmt_insert, "ssdi", $full_name, $shift, $total_amount, $_SESSION["user"]["id"]);

    if (mysqli_stmt_execute($stmt_insert)) {
        // Get the last inserted report ID
        $report_id = mysqli_insert_id($conn);

        // Insert item details into the report_items table
        $sql_item_insert = "INSERT INTO report_items (report_id, item_name, quantity, total_price, user_id) VALUES (?, ?, ?, ?, ?)";
        foreach ($sold_items as $item) {
            $stmt_item_insert = mysqli_prepare($conn, $sql_item_insert);
            mysqli_stmt_bind_param($stmt_item_insert, "isidi", $report_id, $item['name'], $item['quantity_sold'], $item['quantity_sold'] * $item['price'], $_SESSION["user"]["id"]);
            mysqli_stmt_execute($stmt_item_insert);
            mysqli_stmt_close($stmt_item_insert);
        }

        echo "<div class='alert alert-success'>Report generated successfully!</div>";
    } else {
        echo "<div class='alert alert-danger'>Error generating report: " . mysqli_error($conn) . "</div>";
    }
    mysqli_stmt_close($stmt_insert);
}

// Fetch reports for the selected date
$sql_fetch_reports = "
    SELECT r.report_id AS report_id, r.full_name, r.shift, r.total_amount, r.report_date, ri.item_name, ri.quantity, ri.total_price 
    FROM reports r 
    LEFT JOIN report_items ri ON r.report_id = ri.report_id 
    WHERE DATE(r.report_date) = ?
    ORDER BY r.report_id DESC
";
$stmt_fetch_reports = mysqli_prepare($conn, $sql_fetch_reports);
mysqli_stmt_bind_param($stmt_fetch_reports, "s", $selected_date);
mysqli_stmt_execute($stmt_fetch_reports);
$result_reports = mysqli_stmt_get_result($stmt_fetch_reports);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="style.css">
    <title>Reports</title>
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
        <h1>Reports</h1>

        <!-- Form to select a date for reports -->
        <form method="POST" class="mb-4">
            <label for="selected_date" class="form-label">Select Date</label>
            <input type="date" id="selected_date" name="selected_date" value="<?php echo htmlspecialchars($selected_date); ?>" class="form-control">
            <button type="submit" class="btn btn-primary mt-3">View Reports</button>
        </form>

        <div class="accordion" id="reportsAccordion">
            <?php
            $current_report_id = null;
            $items_html = '';

            while ($row = mysqli_fetch_assoc($result_reports)):
                // Start a new report if we have a new report ID
                if ($current_report_id !== $row['report_id']):
                    // Output previous report if it's not the first row
                    if ($current_report_id !== null):
            ?>
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="heading<?php echo $current_report_id; ?>">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?php echo $current_report_id; ?>" aria-expanded="false" aria-controls="collapse<?php echo $current_report_id; ?>">
                                Report ID: <?php echo htmlspecialchars($current_report_id); ?> | Full Name: <?php echo htmlspecialchars($previous_full_name); ?> | Shift: <?php echo htmlspecialchars($previous_shift); ?> | Total Amount: RM <?php echo number_format($previous_total_amount, 2); ?> | Report Date: <?php echo htmlspecialchars(date('d-F-Y H:i:s', strtotime($previous_report_date))); ?>
                            </button>
                        </h2>
                        <div id="collapse<?php echo $current_report_id; ?>" class="accordion-collapse collapse" aria-labelledby="heading<?php echo $current_report_id; ?>" data-bs-parent="#reportsAccordion">
                            <div class="accordion-body">
                                <table class="table table-sm mb-0">
                                    <thead>
                                        <tr>
                                            <th>Item Name</th>
                                            <th>Quantity Sold</th>
                                            <th>Total Price (RM)</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php echo $items_html; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
            <?php
                    endif;

                    // Set values for the new report
                    $current_report_id = $row['report_id'];
                    $previous_full_name = $row['full_name'];
                    $previous_shift = $row['shift'];
                    $previous_total_amount = $row['total_amount'];
                    $previous_report_date = $row['report_date'];
                    $items_html = ''; // Reset items HTML buffer
                endif;

                // Add the current item to the items HTML buffer
                if (!empty($row['item_name'])):
                    $items_html .= '<tr>
                                        <td>' . htmlspecialchars($row['item_name']) . '</td>
                                        <td>' . htmlspecialchars($row['quantity'] ?? 0) . '</td>
                                        <td>RM ' . number_format($row['total_price'] ?? 0, 2) . '</td>
                                    </tr>';
                endif;
            endwhile;

            // Output the last report
            if ($current_report_id !== null):
            ?>
                <div class="accordion-item">
                    <h2 class="accordion-header" id="heading<?php echo $current_report_id; ?>">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?php echo $current_report_id; ?>" aria-expanded="false" aria-controls="collapse<?php echo $current_report_id; ?>">
                            Report ID: <?php echo htmlspecialchars($current_report_id); ?> | Full Name: <?php echo htmlspecialchars($previous_full_name); ?> | Shift: <?php echo htmlspecialchars($previous_shift); ?> | Total Amount: RM <?php echo number_format($previous_total_amount, 2); ?> | Report Date: <?php echo htmlspecialchars(date('d-F-Y H:i:s', strtotime($previous_report_date))); ?>
                        </button>
                    </h2>
                    <div id="collapse<?php echo $current_report_id; ?>" class="accordion-collapse collapse" aria-labelledby="heading<?php echo $current_report_id; ?>" data-bs-parent="#reportsAccordion">
                        <div class="accordion-body">
                            <table class="table table-sm mb-0">
                                <thead>
                                    <tr>
                                        <th>Item Name</th>
                                        <th>Quantity Sold</th>
                                        <th>Total Price (RM)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php echo $items_html; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
mysqli_stmt_close($stmt_fetch_reports);
mysqli_close($conn);
?>
