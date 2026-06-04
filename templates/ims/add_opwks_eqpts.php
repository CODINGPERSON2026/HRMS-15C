<?php
session_start();
require_once "auth.php";
require_admin();
require_once "connect.php";
?>
<!DOCTYPE html>
<html>
<head>
<title>Add OPWKS Equipment (Table Format)</title>
<link rel="stylesheet" href="css/bootstrap.min.css">

<style>
body{
    background:#eef5ff;
    padding:20px;
    font-family:Segoe UI;
}
.card{
    max-width:1200px;
    margin:auto;
    padding:25px;
    border-radius:14px;
    background:#fff;
    box-shadow:0 6px 18px rgba(0,0,0,.15);
}
table th, table td{
    vertical-align:middle !important;
    text-align:center;
}
.readonly{
    background:#f1f3f5;
}
</style>
</head>

<body>

<div class="card">
<h3 class="text-center mb-3">➕ Add OPWKS Equipment (Table Format)</h3>
<hr>

<form method="POST" action="insert_opwks_eqpts.php">

<table class="table table-bordered table-striped" id="equipTable">
    <thead class="table-dark">
        <tr>
            <th>SER NO</th>
            <th>JOB NO / NAR NO</th>
            <th>NOMENCLATURE</th>
            <th>A/U</th>
            <th>QTY</th>
            <th>COST OF EACH</th>
            <th>TOTAL AMOUNT</th>
            <th>DISTRIBUTION (AUTO)</th>
            <th>❌</th>
        </tr>
    </thead>

    <tbody>
        <tr>
            <td class="ser">1</td>

            <td>
                <input type="text" name="job_no[]" class="form-control">
            </td>

            <td>
                <input type="text" name="equipment_name[]" class="form-control" required>
            </td>

            <td>
                <select name="au[]" class="form-control">
                    <option>Nos</option>
                    <option>Set</option>
                    <option>Pair</option>
                    <option>Mtrs</option>
                </select>
            </td>

            <td>
                <input type="number" name="qty[]" class="form-control qty" min="1" required>
            </td>

            <td>
                <input type="number" name="cost[]" step="0.01" class="form-control cost">
            </td>

            <td>
                <input type="number" class="form-control total readonly" readonly>
            </td>

            <td>
                <input type="text" class="form-control readonly"
                       value="Auto on Issue / Return" readonly>
            </td>

            <td>
                <button type="button" class="btn btn-danger btn-sm removeRow">X</button>
            </td>
        </tr>
    </tbody>
</table>

<button type="button" class="btn btn-primary" id="addRowBtn">➕ Add Row</button>

<button type="submit" class="btn btn-success float-end">✅ Save Equipment</button>

<a href="opwks.php" class="btn btn-secondary mt-3">⬅ Back</a>

</form>
</div>

<script>
// ADD ROW
document.getElementById('addRowBtn').onclick = function(){
    let table = document.querySelector('#equipTable tbody');
    let rowCount = table.rows.length + 1;

    let row = table.insertRow();
    row.innerHTML = `
        <td class="ser">${rowCount}</td>

        <td><input type="text" name="job_no[]" class="form-control"></td>

        <td><input type="text" name="equipment_name[]" class="form-control" required></td>

        <td>
            <select name="au[]" class="form-control">
                <option>Nos</option>
                <option>Set</option>
                <option>Pair</option>
                <option>Mtrs</option>
            </select>
        </td>

        <td><input type="number" name="qty[]" class="form-control qty" min="1" required></td>

        <td><input type="number" name="cost[]" step="0.01" class="form-control cost"></td>

        <td><input type="number" class="form-control total readonly" readonly></td>

        <td>
            <input type="text" class="form-control readonly"
                   value="Auto on Issue / Return" readonly>
        </td>

        <td>
            <button type="button" class="btn btn-danger btn-sm removeRow">X</button>
        </td>
    `;
};

// REMOVE ROW
document.addEventListener('click', function(e){
    if(e.target.classList.contains('removeRow')){
        e.target.closest('tr').remove();
        updateSerial();
    }
});

// UPDATE SERIAL NO
function updateSerial(){
    document.querySelectorAll('.ser').forEach((el, i)=>{
        el.innerText = i + 1;
    });
}

// AUTO TOTAL CALCULATION
document.addEventListener('input', function(e){
    if(e.target.classList.contains('qty') || e.target.classList.contains('cost')){
        let row = e.target.closest('tr');
        let qty = parseFloat(row.querySelector('.qty').value) || 0;
        let cost = parseFloat(row.querySelector('.cost').value) || 0;
        row.querySelector('.total').value = (qty * cost).toFixed(2);
    }
});
</script>

</body>
</html>
