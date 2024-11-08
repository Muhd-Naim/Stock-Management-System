<?php
session_start();
// Check if user is logged in and if they are an admin
if (!isset($_SESSION["user"]) || $_SESSION["user"]["role"] != 'admin') {
   header("Location: login.php");
   exit();
}

require_once "database.php"; // Ensure you include your database connection file

// Fetch current stock data
$sql = "SELECT * FROM stocks"; // Assuming your stock table is named 'stocks'
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
    <title href="admin_dashboard.php">Admin Dashboard</title>
</head>
<body>
    <!-- Navbar -->
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
                    <li class="nav-item">
                        <a class="nav-link" href="report.php">Report</a>
                    </li>
                </ul>
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container mt-5">

        <h2>Current Stock</h2>
        <table class="table table-bordered mt-3">
            <thead class="table-dark">
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Quantity</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($result && mysqli_num_rows($result) > 0) {
                    while ($row = mysqli_fetch_assoc($result)) {
                        echo "<tr>";
                        echo "<td>" . $row["ID"] . "</td>";
                        echo "<td>" . $row["Name"] . "</td>"; 
                        echo "<td>" . $row["Quantity"] . "</td>"; 
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='3' class='text-center'>No stock available</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</body>
</html>
