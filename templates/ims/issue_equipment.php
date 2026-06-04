<?php
session_start();
require_once "auth.php";
require_admin();
require_once "connect.php";

/* ======================================================
   INPUT
   ====================================================== */
$equipment_id = (int)($_GET['id'] ?? 0);
if ($equipment_id <= 0) {
    die("Invalid Equipment ID");
}



/* ======================================================
   FETCH EQUIPMENT
   ====================================================== */
$stmt = $connect->prepare("
    SELECT 
        em.id,
        em.equipment_name,
        em.lp_no,
        em.cat_part_no,
        em.au,
        em.qty_available,
        em.cost,
        gm.grant_name,
        gm.grant_type
    FROM equipment_master em
    JOIN grants_master gm ON em.grant_id = gm.id
    WHERE em.id = ?
");
$stmt->bind_param("i", $equipment_id);
$stmt->execute();
$eq = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$eq) {
    die("Equipment not found");
}

/* ======================================================
   SESSION INIT
   ====================================================== */
$_SESSION['ISSUE_LIST'] = $_SESSION['ISSUE_LIST'] ?? [];

/* ======================================================
   ADD ITEM
   ====================================================== */
if (isset($_POST['add_item'])) {

    $qty        = (int)($_POST['issue_qty'] ?? 0);
    $issue_type = $_POST['issue_type'] ?? '';
    $unit_name  = trim($_POST['unit_name'] ?? '');

    if ($qty <= 0 || $qty > $eq['qty_available']) {
        die("Invalid Quantity");
    }
    if ($issue_type === '' || $unit_name === '') {
        die("Issued To required");
    }

    $_SESSION['ISSUE_LIST'][] = [
        'equipment_id' => $eq['id'],
        'lp'           => $eq['lp_no'],
        'cat'          => $eq['cat_part_no'],
        'name'         => $eq['equipment_name'],
        'au'           => $eq['au'],
        'qty'          => $qty,
        'cost'         => $eq['cost'],
        'issue_type'   => $issue_type,
        'unit_name'    => $unit_name
    ];

    $_SESSION['VOUCHER_HEADER'] = [
    'unit_name'  => $unit_name,
    'grant_type' => $eq['grant_type']
];
}

/* ======================================================
   REMOVE ITEM
   ====================================================== */
if (isset($_GET['return'])) {
    unset($_SESSION['ISSUE_LIST'][$_GET['return']]);
    $_SESSION['ISSUE_LIST'] = array_values($_SESSION['ISSUE_LIST']);
}

/* ======================================================
   GENERATE VOUCHER
====================================================== */
if (isset($_POST['generate_voucher'])) {

    if (empty($_SESSION['ISSUE_LIST'])) {
        die("No items added");
    }

    $issued_to = $_SESSION['ISSUE_LIST'][0]['unit_name'];

    foreach ($_SESSION['ISSUE_LIST'] as $key => $item) {

        $stmt = $connect->prepare("
            SELECT id, barcode
            FROM equipment_items
            WHERE equipment_id = ?
            AND status = 'AVAILABLE'
            LIMIT ?
        ");
        $stmt->bind_param("ii", $item['equipment_id'], $item['qty']);
        $stmt->execute();
        $result = $stmt->get_result();

        $barcode_ids = [];
        $barcode_values = [];

        while($row = $result->fetch_assoc()){
            $barcode_ids[] = $row['id'];
            $barcode_values[] = $row['barcode'];
        }
        $stmt->close();

        if(count($barcode_ids) < $item['qty']){

        $remaining = $item['qty'] - count($barcode_ids);

        for($i=1; $i<=$remaining; $i++){

            $new_barcode = "BC-" . strtoupper(uniqid());

            // New barcode insert karo
            $ins = $connect->prepare("
                INSERT INTO equipment_items (equipment_id, barcode, status)
                VALUES (?, ?, 'AVAILABLE')
            ");
            $ins->bind_param("is", $item['equipment_id'], $new_barcode);
            $ins->execute();

            $new_id = $ins->insert_id;
            $ins->close();

            // array me add karo taaki issue ho sake
            $barcode_ids[] = $new_id;
            $barcode_values[] = $new_barcode;
        }
    }

        foreach($barcode_ids as $bid){
            $stmt2 = $connect->prepare("
                UPDATE equipment_items
                SET status = 'ISSUED'
                WHERE id = ?
            ");
            $stmt2->bind_param("i", $bid);
            $stmt2->execute();
            $stmt2->close();
        }

        $stmt3 = $connect->prepare("
            UPDATE equipment_master
            SET qty_available = qty_available - ?
            WHERE id = ?
        ");
        $stmt3->bind_param("ii", $item['qty'], $item['equipment_id']);
        $stmt3->execute();
        $stmt3->close();

        $_SESSION['ISSUE_LIST'][$key]['barcodes'] = $barcode_values;

        $stmt4 = $connect->prepare("
            INSERT INTO equipment_txn
            (equipment_id, txn_type, qty, issue_type, unit_name, created_by)
            VALUES (?, 'ISSUE', ?, ?, ?, ?)
        ");
        $stmt4->bind_param(
            "iissi",
            $item['equipment_id'],
            $item['qty'],
            $item['issue_type'],
            $issued_to,
            $_SESSION['id']
        );
        $stmt4->execute();
        $stmt4->close();
    }

    // 🔥 VERY IMPORTANT
    $_SESSION['VOUCHER_ITEMS'] = $_SESSION['ISSUE_LIST'];
    $_SESSION['ISSUE_LIST'] = [];

  $grantType = strtoupper(trim($eq['grant_type']));

if (
    strpos($grantType, 'PUBLIC') !== false || 
    strpos($grantType, 'REGTL') !== false ||
    strpos($grantType, 'OPWKS') !== false
) {

    header("Location: loan_cost_voucher.php?id=".$equipment_id);

} else {

    header("Location: voucher_editable.php?id=".$equipment_id);
}
exit;
}
?>
<!DOCTYPE html>
<html>
<head>
<title>Issue Equipment</title>
<link rel="stylesheet" href="css/bootstrap.min.css">
<style>
body{background:#eef5ff;padding:25px;font-family:Segoe UI}
.card{max-width:900px;margin:auto;background:#fff;padding:22px;border-radius:14px}
.info-box{background:#f9fbfc;border:1px solid #ddd;border-radius:12px;padding:16px;margin-bottom:20px}
.info-grid{display:grid;grid-template-columns:1fr 1fr;gap:12px 30px}
.info-item span{font-weight:600}
</style>
</head>

<body>
<div class="card">
<h4 class="text-center">📤 Issue Equipment</h4>
<hr>

<div class="info-box">
<div class="info-grid">
    <div class="info-item"><span>Grant Type :</span> <?= htmlspecialchars($eq['grant_type']) ?></div>
    <div class="info-item"><span>Grant Name :</span> <?= htmlspecialchars($eq['grant_name']) ?></div>
    <div class="info-item"><span>Equipment :</span> <?= htmlspecialchars($eq['equipment_name']) ?></div>
    <div class="info-item"><span>LP No :</span> <?= htmlspecialchars($eq['lp_no']) ?></div>
    <div class="info-item"><span>Cat / Part No :</span> <?= htmlspecialchars($eq['cat_part_no']) ?></div>
    <div class="info-item"><span>Available Qty :</span> <?= $eq['qty_available']." ".$eq['au'] ?></div>
</div>
</div>

<form method="POST">

<label>Issue Type</label>
<select name="issue_type" id="issueType" class="form-control" required>
<option value="">-- Select --</option>
<option value="INTERNAL">Internal</option>
<option value="EXTERNAL">External</option>
</select>

<div id="internalBox" style="display:none" class="mt-2">
<label>Issued To (Store)</label>
<select id="internalUnit" class="form-control">
<option value="">SELECT STORE</option>
<option>HQ CQ STORE</option>
<option>1 CQ STORE</option>
<option>2 CQ STORE</option>
<option>3 CQ STORE</option>
<option>3 COY EFS STORE</option>
<option>3 COY MT STORE</option>
<option>3 COY RADIO ROOM</option>
<option>3 RR STORE</option>
<option>3 COY EFS STORE</option>
<option>LINE STORE</option>
<option>ICN STORE</option>
<option>MCCS STORE</option>
<option>RP STORE</option>
<option>EFS STORE</option>
<option>OP SHIVA STORE</option>
<option>MT STORE</option>
<option>LRW STORE</option>
<option>TM STORE</option>
<option>RHQ STORE</option>
<option>IT STORE</option>
<option>STRONG ROOM</option>
<option>CSO RES</option>
<option>NFS STORE</option>
<option>CABLE TV</option>
<option>OFFR MESS</option>
<option>JCO MESS</option>
<option>3 COY SYSTEM</option>
<option>SPORTS NCO</option>
<option>CSD</option>
<option>RAREMART</option>
<option>ACCT STORE</option>
<option>QM STORE</option>
<option>SIGS BR</option>
<option>RATION STORE</option>
<option>FOL STORE</option>
<option>LIBRARY</option>
<option>PROJECT STORE</option>
<option>CIPHER STORE</option>
<option>MANDIR STORE</option>
<option>REC ROOM</option>
<option>MES NCO</option>
<option>1/2 COY KOTE</option>
<option>HQ COY KOTE</option>
<option>AMN STORE</option>
<option>EDN NCO</option>
<option>3 COY EFS STORE</option>
<option>2 COY EFS STORE</option>
<option>RADIO STORE</option>
<option>2 COY STRONG ROOM</option>
</select>
</div>

<div id="externalBox" style="display:none" class="mt-2">
<label>Issued To (External)</label>
<select id="externalUnit" class="form-control">
<option value="">SELECT STORE</option>
<option>13 ENGR REGT</option>
<option>3 RR BN</option>
<option>15 COSR</option>
<option>VFSR</option>
<option>KFSR</option>
<option>MI ROOM</option>
<option>FAD KHUNMOH</option>
<option>HQ 268 BDE</option>
<option>19 IDSR</option>
<option>28 IDSR</option>
<option>68 MBSC</option>
<option>79 MBSC</option>
<option>14 CESR</option>
<option>14 WEU</option>
<option>25 AD REGT</option>
<option>54 ADV VET HOSP</option>
<option>102 IBSC</option>
<option>109 IBSC</option>
<option>162 TA BN</option>
<option>619 ADBSC</option>
<option>4015 FD HOSP</option>
<option>15 CBS</option>
<option>3 SECT RR</option>
<option>5 SECT RR</option>
<option>7 SECT RR</option>
<option>8 SECT RR</option>
<option>14 RR BN</option>
<option>15 CZW</option>
<option>113 FD REGT</option>
<option>JAKLI REGT</option>
<option>521 NSC</option>
<option>SONAMARG DET</option>
<option>OAF STN HQ</option>
<option>DELHI MS9</option>
<input type="text" id="externalUnit" class="form-control">
</div>

<input type="hidden" name="unit_name" id="finalUnit">

<label class="mt-3">Qty</label>
<input type="number" name="issue_qty" min="1" max="<?= $eq['qty_available'] ?>" class="form-control" required>

<div class="d-flex gap-2 mt-3">
<button name="add_item" class="btn btn-primary">➕ Add Item</button>
<a href="central_view.php" class="btn btn-secondary">⬅ Back</a>
</div>
</form>

<hr>

<h6>📋 Added Items</h6>
<table class="table table-bordered">
<tr><th>#</th><th>LP No</th><th>Nomenclature</th><th>Qty</th><th>Action</th></tr>

<?php if(empty($_SESSION['ISSUE_LIST'])){ ?>
<tr><td colspan="5">No Items Added</td></tr>
<?php } ?>

<?php foreach($_SESSION['ISSUE_LIST'] as $i=>$it){ ?>
<tr>
<td><?= $i+1 ?></td>
<td><?= htmlspecialchars($it['lp']) ?></td>
<td><?= htmlspecialchars($it['name']) ?></td>
<td><?= $it['qty']." ".$it['au'] ?></td>
<td><a href="?id=<?= $equipment_id ?>&return=<?= $i ?>" class="btn btn-sm btn-warning">↩ Return</a></td>
</tr>
<?php } ?>
</table>

<form method="POST">
<button name="generate_voucher" class="btn btn-success w-100">📄 Generate Voucher</button>
</form>

</div>

<script>
const issueType=document.getElementById("issueType");
const internalBox=document.getElementById("internalBox");
const externalBox=document.getElementById("externalBox");
const internalSel=document.getElementById("internalUnit");
const externalInp=document.getElementById("externalUnit");
const finalUnit=document.getElementById("finalUnit");

issueType.onchange=()=>{
 internalBox.style.display="none";
 externalBox.style.display="none";
 finalUnit.value="";
 if(issueType.value==="INTERNAL") internalBox.style.display="block";
 if(issueType.value==="EXTERNAL") externalBox.style.display="block";
};
internalSel.onchange=()=> finalUnit.value=internalSel.value;
externalInp.oninput=()=> finalUnit.value=externalInp.value;
</script>

</body>
</html>
