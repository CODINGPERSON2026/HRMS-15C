<?php
session_start();
include('connect.php');



// Fetch login activity data from the database
$sql = "SELECT * FROM useractivitylog WHERE activity = 'login' ORDER BY datetime DESC";
$result = mysqli_query($connect, $sql);

// Check if there are results
if ($result && mysqli_num_rows($result) > 0) {
    $login_activity = mysqli_fetch_all($result, MYSQLI_ASSOC);
} else {
    $login_activity = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login Activity</title>
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <!-- Include Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body {
            padding: 20px;
            background-color: #f8f9fa;
        }
        .container {
            max-width: 800px;
            margin: auto;
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .table-container {
            margin-top: 20px;
        }
        .table-container table {
            width: 100%;
            border-collapse: collapse;
        }
        .table-container th, .table-container td {
            padding: 8px;
            text-align: left;
            border: 1px solid #dee2e6;
        }
        .table-container th {
            background-color: #007bff;
            color: #fff;
        }
        .table-container tbody tr:nth-child(even) {
            background-color: #f2f2f2;
        }
        .back-link {
            margin-top: 20px;
            text-align: right;
        }
        .back-link a {
            color: #007bff;
            text-decoration: none;
        }
        .back-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="back-link">
            <a href="dboard.php"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
        </div>
        <h1>Login Activity</h1>
        <div class="table-container">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>User ID</th>
                        <th>Username</th>
                        <th>Login Time</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($login_activity as $log): ?>
                        <tr>
                            <td><?php echo $log['userid']; ?></td>
                            <td><?php echo $log['username']; ?></td>
                            <td><?php echo $log['datetime']; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
