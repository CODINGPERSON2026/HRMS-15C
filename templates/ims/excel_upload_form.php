<?php
session_start();
// require_once "auth.php";
// require_admin();

?>
<!DOCTYPE html>
<html>
<head>
<title>Upload Equipment Excel</title>
<link rel="stylesheet" href="css/bootstrap.min.css">
</head>
<body class="bg-light">

<div class="container mt-5">
<div class="card shadow p-4">
<h4 class="text-center mb-3">📥 Upload Equipment (Excel)</h4>

<form method="POST" action="upload_central_excel.php" enctype="multipart/form-data">

<label>Select Excel File (.xlsx)</label>
<input type="file" name="excel_file" class="form-control" accept=".xlsx,.xls" required>

<button class="btn btn-success mt-3 w-100">⬆ Upload & Save</button>
<a href="dboard.php" class="btn btn-secondary mt-2 w-100">⬅ Back</a>

</form>
</div>
</div>

</body>
</html>
