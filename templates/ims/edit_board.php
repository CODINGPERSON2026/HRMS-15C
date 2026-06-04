<?php
session_start();
require_once "auth.php";
require_admin();
require_once "connect.php";

$id = intval($_GET['id'] ?? 0);
if ($id <= 0) die("Invalid Board ID");

/* FETCH BOARD */
$stmt = $connect->prepare("SELECT * FROM ord_board WHERE id=?");
$stmt->bind_param("i",$id);
$stmt->execute();
$res = $stmt->get_result();
if($res->num_rows === 0) die("Board not found");
$row = $res->fetch_assoc();

/* 🔥 SAFE JSON DECODE (OLD + NEW FORMAT SUPPORT) */
$grantsRows = [];
if ($row['board_type']==='GRANTS' && !empty($row['grants_table'])) {
    $decoded = json_decode($row['grants_table'], true);
    if (is_array($decoded)) {
        foreach ($decoded as $g) {
            $grantsRows[] = [
                'ser'     => $g['ser_no'] ?? $g['ser'] ?? '',
                'type'    => $g['grant_type_tbl'] ?? $g['type'] ?? '',
                'amount'  => $g['unsv_amt'] ?? $g['amount'] ?? '',
                'appx'    => $g['appx'] ?? '',
                'remarks' => $g['remarks'] ?? ''
            ];
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
<title>Edit Board</title>
<link rel="stylesheet" href="css/bootstrap.min.css">

<style>
body{background:#eef5ff;padding:20px;font-family:Segoe UI;}
.card{
    max-width:1100px;margin:auto;padding:26px;border-radius:14px;
    background:#fff;box-shadow:0 6px 18px rgba(0,0,0,.15);
}
.grid{display:grid;grid-template-columns:1fr 1fr;gap:18px;}
.full{grid-column:1/3}
textarea{resize:none}
</style>
</head>

<body>
<div class="card">
<h3 class="text-center mb-2">✏ Edit Board of Officers</h3>
<hr>

<form method="POST" action="update_board.php">
<input type="hidden" name="id" value="<?= $row['id'] ?>">

<div class="grid">

<!-- BOARD TYPE -->
<div class="full">
<label>Board Type</label>
<select name="board_type" id="boardType" class="form-control">
<option value="ORD" <?= $row['board_type']=='ORD'?'selected':'' ?>>ORD</option>
<option value="OPWKS" <?= $row['board_type']=='OPWKS'?'selected':'' ?>>OPWKS</option>
<option value="GRANTS" <?= $row['board_type']=='GRANTS'?'selected':'' ?>>GRANTS</option>
</select>
</div>

<div><label>Proceedings of</label>
<input name="proceedings_of" class="form-control"
value="<?= htmlspecialchars($row['proceedings_of']) ?>"></div>

<div><label>Assembled At</label>
<input name="assembled_at" class="form-control"
value="<?= htmlspecialchars($row['assembled_at']) ?>"></div>

<div><label>On the Day Of</label>
<input name="on_day_text" class="form-control"
value="<?= htmlspecialchars($row['on_day_text']) ?>"></div>

<div><label>By the Order Of</label>
<input name="order_of" class="form-control"
value="<?= htmlspecialchars($row['order_of']) ?>"></div>

<div class="full">
<label>For the Purpose Of</label>
<textarea name="purpose" rows="3" class="form-control"><?= htmlspecialchars($row['purpose']) ?></textarea>
</div>

<div><label>Presiding Officer</label>
<input name="presiding_officer" class="form-control"
value="<?= htmlspecialchars($row['presiding_officer']) ?>"></div>

<div><label>Member 1</label>
<input name="member1" class="form-control"
value="<?= htmlspecialchars($row['member1']) ?>"></div>

<div><label>Member 2</label>
<input name="member2" class="form-control"
value="<?= htmlspecialchars($row['member2']) ?>"></div>

<div class="full"><label>Point 1</label>
<textarea name="point1" class="form-control"><?= htmlspecialchars($row['point1']) ?></textarea></div>

<div class="full"><label>Point 2</label>
<textarea name="point2" class="form-control"><?= htmlspecialchars($row['point2']) ?></textarea></div>

<div class="full"><label>Point 3</label>
<textarea name="point3" class="form-control"><?= htmlspecialchars($row['point3']) ?></textarea></div>

<!-- 🔥 GRANTS SECTION -->
<div id="grantsSection" class="full" style="<?= $row['board_type']==='GRANTS'?'':'display:none' ?>">

<h5 class="mt-3">UNSV / Grants Details</h5>

<table class="table table-bordered">
<thead class="table-light">
<tr>
<th>Ser No</th>
<th>Type of Grants</th>
<th>Amt of UNSV Items</th>
<th>Appx</th>
<th>Remarks</th>
</tr>
</thead>
<tbody id="unsvBody">

<?php if ($grantsRows): foreach ($grantsRows as $g): ?>
<tr>
<td><input name="ser_no[]" class="form-control" value="<?= htmlspecialchars($g['ser']) ?>"></td>
<td><input name="grant_type_tbl[]" class="form-control" value="<?= htmlspecialchars($g['type']) ?>"></td>
<td><input name="unsv_amt[]" class="form-control" value="<?= htmlspecialchars($g['amount']) ?>"></td>
<td><input name="appx[]" class="form-control" value="<?= htmlspecialchars($g['appx']) ?>"></td>
<td><input name="remarks[]" class="form-control" value="<?= htmlspecialchars($g['remarks']) ?>"></td>
</tr>
<?php endforeach; else: ?>
<tr>
<td><input name="ser_no[]" class="form-control"></td>
<td><input name="grant_type_tbl[]" class="form-control"></td>
<td><input name="unsv_amt[]" class="form-control"></td>
<td><input name="appx[]" class="form-control"></td>
<td><input name="remarks[]" class="form-control"></td>
</tr>
<?php endif; ?>

</tbody>
</table>

<button type="button" class="btn btn-sm btn-secondary" onclick="addRow()">➕ Add Row</button>

<label class="mt-3">Recommendation of the Board</label>
<textarea name="recommendation" class="form-control"><?= htmlspecialchars($row['recommendations']) ?></textarea>

</div>

<!-- 🔥 SIGNATURES (CRITICAL FIX) -->
<div><label>Presiding Officer (Signature)</label>
<input name="po_sign" class="form-control" value="<?= htmlspecialchars($row['po_sign']) ?>"></div>

<div><label>Member 1 (Signature)</label>
<input name="m1_sign" class="form-control" value="<?= htmlspecialchars($row['m1_sign']) ?>"></div>

<div><label>Member 2 (Signature)</label>
<input name="m2_sign" class="form-control" value="<?= htmlspecialchars($row['m2_sign']) ?>"></div>

<div><label>Station</label>
<input name="station" class="form-control" value="<?= htmlspecialchars($row['station']) ?>"></div>

<div><label>Dated</label>
<input type="date" name="dated" class="form-control" value="<?= $row['dated'] ?>"></div>

</div>

<button class="btn btn-primary w-100 mt-4">💾 Update Board</button>
<a href="board_list.php" class="btn btn-secondary w-100 mt-2">⬅ Back</a>

</form>
</div>

<script>
function addRow(){
document.getElementById("unsvBody").insertAdjacentHTML("beforeend",`
<tr>
<td><input name="ser_no[]" class="form-control"></td>
<td><input name="grant_type_tbl[]" class="form-control"></td>
<td><input name="unsv_amt[]" class="form-control"></td>
<td><input name="appx[]" class="form-control"></td>
<td><input name="remarks[]" class="form-control"></td>
</tr>`);
}
document.getElementById("boardType").addEventListener("change",e=>{
document.getElementById("grantsSection").style.display =
e.target.value==="GRANTS"?"block":"none";
});
</script>

</body>
</html>
