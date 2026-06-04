<?php
session_start();
require_once "auth.php";
require_admin();
require_once "connect.php";
?>
<!DOCTYPE html>
<html>
<head>
<title>ADD ASTB BOARD</title>
<link rel="stylesheet" href="css/bootstrap.min.css">

<style>
body{
    background:#eef5ff;
    padding:20px;
    font-family:Segoe UI;
}
.card{
    max-width:950px;
    margin:auto;
    padding:26px;
    border-radius:14px;
    background:#fff;
    box-shadow:0 6px 18px rgba(0,0,0,.15);
}
.grid{
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:18px;
}
.full{grid-column:1/3}
textarea{resize:none;}
table input{font-size:13px}
</style>
</head>

<body>

<div class="card">
<h3 class="text-center mb-2">➕ ADD ASTB BOARD</h3>
<hr>

<form method="POST" action="insert_board.php" onsubmit="prepareGrantsJSON()">

<div class="grid">

<!-- BOARD TYPE -->
<div class="full">
<label>Board Type</label>
<select name="board_type" id="boardType" class="form-control" required>
    <option value="ORD">ORD BOARD</option>
    <option value="OPWKS">OPWKS BOARD</option>
    <option value="GRANTS">GRANTS BOARD</option>
</select>
</div>

<!-- BASIC -->
<div>
<label>Proceedings of</label>
<input type="text" name="proceedings_of" class="form-control"
value="Board of Officer" required>
</div>

<div>
<label>Assembled At</label>
<input type="text" name="assembled_at" class="form-control"
value="15 Corps Engg Sig Regt C/o 56 APO" required>
</div>

<!-- DATE -->
<div>
<label>On the Day Of</label>
<input type="text" name="on_day_text" id="onDayText"
class="form-control mb-1" readonly required>

<input type="date" id="onDayPicker"
class="form-control" required
onchange="formatOnDay(this.value)">
</div>

<div>
<label>By the Order Of</label>
<input type="text" name="order_of" class="form-control">
</div>

<!-- PURPOSE -->
<div class="full">
<label>For the Purpose Of</label>
<textarea name="purpose" id="purposeBox"
rows="3" class="form-control" required></textarea>
</div>

<!-- OFFICERS -->
<div>
<label>Presiding Officer</label>
<input type="text" name="presiding_officer" class="form-control">
</div>

<div>
<label>Member 1</label>
<input type="text" name="member1" class="form-control">
</div>

<div>
<label>Member 2</label>
<input type="text" name="member2" class="form-control">
</div>

<!-- POINTS -->
<div class="full">
<label>Point 1</label>
<textarea name="point1" rows="2" class="form-control"></textarea>
</div>

<div class="full">
<label>Point 2</label>
<textarea name="point2" rows="2" class="form-control"></textarea>
</div>

<div class="full">
<label>Point 3</label>
<textarea name="point3" rows="2" class="form-control"></textarea>
</div>

<!-- ================= GRANTS SECTION ================= -->
<div id="grantsSection" class="full" style="display:none;">

<h6 class="mt-3 fw-bold">UNSV / Grants Details</h6>

<table class="table table-bordered mt-2" id="grantsTable">
<thead class="table-light">
<tr>
    <th width="8%">Ser</th>
    <th>Type of Grants</th>
    <th width="18%">Amt of UNSV Items</th>
    <th width="12%">Appx</th>
    <th>Remarks</th>
</tr>
</thead>

<tbody id="unsvBody">
<tr>
    <td class="text-center">1</td>
    <td><input class="form-control"></td>
    <td><input class="form-control amt" oninput="calcTotal()"></td>
    <td><input class="form-control"></td>
    <td><input class="form-control"></td>
</tr>
</tbody>

<tfoot>
<tr>
    <th colspan="2" class="text-end">TOTAL</th>
    <th><input id="totalAmt" class="form-control" readonly></th>
    <th colspan="2"></th>
</tr>
</tfoot>
</table>

<button type="button" class="btn btn-sm btn-secondary"
onclick="addUnsvRow()">➕ Add Row</button>

<hr>

<label>Recommendation of the Board</label>
<textarea name="recommendations" rows="3" class="form-control">
A separate BOO should be detailed for Auction/Scrap/Destruction for the stores
which are declared UNSV by this bd.
</textarea>

</div>

<input type="hidden" name="grants_table" id="grantsTableData">

<!-- SIGNATURES -->
<div>
<label>Presiding Officer (Signature Name)</label>
<input type="text" name="po_sign" class="form-control">
</div>

<div>
<label>Member 1 (Signature Name)</label>
<input type="text" name="m1_sign" class="form-control">
</div>

<div>
<label>Member 2 (Signature Name)</label>
<input type="text" name="m2_sign" class="form-control">
</div>

<div>
<label>Station</label>
<input type="text" name="station" value="C/o 56 APO" class="form-control">
</div>

<div>
<label>Dated</label>
<input type="date" name="dated" class="form-control">
</div>

</div>

<button class="btn btn-success mt-4 w-100">💾 Save Board</button>
<a href="board_list.php" class="btn btn-secondary mt-3 w-100">⬅ Back</a>

</form>
</div>

<!-- ================= JS ================= -->
<script>
function formatOnDay(val){
    if(!val) return;
    const d = new Date(val);
    const day = String(d.getDate()).padStart(2,'0');
    const mon = d.toLocaleString('en-GB',{month:'short'});
    const yr  = d.getFullYear();
    onDayText.value = `${day} ${mon} ${yr} and subsequent days`;
}

function addUnsvRow(){
    const tbody = document.getElementById("unsvBody");
    const rno = tbody.rows.length + 1;
    tbody.insertAdjacentHTML("beforeend",`
    <tr>
        <td class="text-center">${rno}</td>
        <td><input class="form-control"></td>
        <td><input class="form-control amt" oninput="calcTotal()"></td>
        <td><input class="form-control"></td>
        <td><input class="form-control"></td>
    </tr>`);
}

function calcTotal(){
    let total = 0;
    document.querySelectorAll(".amt").forEach(i=>{
        total += Number(i.value || 0);
    });
    document.getElementById("totalAmt").value = total;
}

function prepareGrantsJSON(){
    if(boardType.value !== "GRANTS") return;

    let data = [];
    document.querySelectorAll("#unsvBody tr").forEach(tr=>{
        const inp = tr.querySelectorAll("input");
        data.push({
            ser: tr.cells[0].innerText,
            type: inp[0].value,
            amount: inp[1].value,
            appx: inp[2].value,
            remarks: inp[3].value
        });
    });
    document.getElementById("grantsTableData").value = JSON.stringify(data);
}

const boardType = document.getElementById("boardType");
const grantsBox = document.getElementById("grantsSection");
const purposeBox = document.getElementById("purposeBox");

boardType.addEventListener("change", ()=>{
    grantsBox.style.display = boardType.value==="GRANTS" ? "block":"none";

    if(boardType.value==="ORD"){
        purposeBox.value =
        "To carry out Annual Stock taking bd for the year 2026-27 in respect of Tech/General/LCC & Arms held on charge of the unit.";
    }
    if(boardType.value==="OPWKS"){
        purposeBox.value =
        "To carry out Annual Stock taking bd for the year 2026-27 in respect of OPWKS Assets held on charge of the unit.";
    }
    if(boardType.value==="GRANTS"){
        purposeBox.value =
        "To carry out Board proceedings in respect of UNSV items under various grants.";
    }
});
</script>

</body>
</html>
