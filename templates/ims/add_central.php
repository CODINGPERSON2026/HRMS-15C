<?php
session_start();
require_once "auth.php";
require_admin();
require_once "connect.php";

/* MASTER GRANTS : TYPE → GRANT → SUB GRANT */
$GRANTS = [
  "TECH/ORD/ACSFP" => [
    "ORD" => ["TECH","GEN","ARMS"],
    "ACSFP" => [],
    "SECT"  => [],
    "LOAN"  => ["TECH","GEN","ARMS","ACSFP","SECT"]
  ],

  "PUBLIC GRANT" => [
    "ACG GRANT"=>[],"ATG GRANT"=>[],"AMENITY GRANT"=>[],
    "ETG GRANT"=>[],"SWSG GRANT"=>[],"TT&IE GRANT"=>[],
    "LPSS GRANT"=>[],"SAG GRANT"=>[],"TAG GRANT"=>[],
    "IT GRANT"=>[],"SRE GRANT"=>[],"MISC GRANT"=>[],
    "LOAN"=>["ACG GRANT","ATG GRANT","AMENITY GRANT","ETG GRANT",
             "SWSG GRANT","TT&IE GRANT","LPSS GRANT","SAG GRANT",
             "TAG GRANT","IT GRANT","SRE GRANT","MISC GRANT"]
  ],

  "REGTL PROPERTIES" => [
    "REGT FUND"=>[],"CSD FUND"=>[],"OFFR MESS FUND"=>[],
    "CSD QD FUND"=>[],"JCO MESS FUND"=>[],"RAREMART FUND"=>[],
    "CABLE TV FUND"=>[]
  ],

  /* ✅ NFS ADDED (NO SUB GRANT) */
  "NFS" => [
    "GOFNMS" => [],
    "IPMPLS" => [],
    "DWDM" => [],
    "MW" => [],
    "STATIC SATL" => [],
    "PORTABLE SATL" => [],
    "HC-MCEU" => [],
    "LC-MCEU" => [],
    "OFC" => []      // ✅ ADDED
],
  /* ✅ OPWKS ADDED FIRST */
  "OPWKS" => [
    "OPWKS" => []
  ]

];
?>
<!DOCTYPE html>
<html>
<head>
<title>Add Equipment (Grant Wise)</title>
<link rel="stylesheet" href="css/bootstrap.min.css">
<style>
body{background:#eef5ff;padding:20px;font-family:Segoe UI}
.card{
    max-width:820px;margin:auto;padding:24px;
    border-radius:14px;background:#fff;
    box-shadow:0 6px 18px rgba(0,0,0,.15)
}
.grid{display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px}
.full{grid-column:1/4}
</style>
</head>

<body>

<div class="card">
<h3 class="text-center">➕ ADD EQUIPMENTS</h3>
<hr>

<form method="POST" action="insert_central.php">

<div class="grid">

<div>
<label>GRANT TYPE</label>
<select id="grantType" name="grant_type" class="form-control" required>
<option value="">-- Select --</option>
<?php foreach($GRANTS as $g=>$v){ ?>
<option value="<?= $g ?>"><?= $g ?></option>
<?php } ?>
</select>
</div>

<div>
<label>GRANT</label>
<select id="grantName" name="grant_name" class="form-control" required disabled>
<option value="">-- Select --</option>
</select>
</div>

<div>
<label>SUB GRANT</label>
<select id="subGrant" name="sub_grant" class="form-control" disabled>
<option value="">-- Select --</option>
</select>
</div>

<div class="full">
<label>EQUIPMENT NAME</label>
<input type="text" name="equipment_name" class="form-control" required>
</div>

<div>
<label id="lpLabel">LP NO</label>
<input type="text" name="lp_no" class="form-control">
</div>

<div id="catPartBox">
<label>CAT / PART NO</label>
<input type="text" name="cat_part_no" class="form-control">
</div>

<div>
<label>A/U</label>
<select name="au" class="form-control">
<option>Nos</option>
<option>Set</option>
<option>Pair</option>
<option>Mtrs</option>
</select>
</div>

<div>
<label>DATE OF RECEIVED</label>
<input type="date" name="received_date" class="form-control" required>
</div>

<div>
<label>QTY RECEIVED</label>
<input type="number" name="qty_received" min="1" class="form-control" required>
</div>

<div id="costBox">
<label>COST</label>
<input type="number" step="0.01" name="cost" class="form-control">
</div>

</div>

<button class="btn btn-success mt-4 w-100">✅ Add Equipment</button>
<a href="dboard.php" class="btn btn-secondary mt-3 w-100">⬅ Back</a>

</form>
</div>

<script>
const GRANTS = <?= json_encode($GRANTS) ?>;

const typeSel  = document.getElementById('grantType');
const grantSel = document.getElementById('grantName');
const subSel   = document.getElementById('subGrant');
const costBox  = document.getElementById('costBox');
const catBox   = document.getElementById('catPartBox');

typeSel.onchange = () => {

  grantSel.innerHTML = '<option value="">-- Select --</option>';
  subSel.innerHTML   = '<option value="">-- Select --</option>';
  subSel.disabled = true;
  grantSel.disabled = true;

  if(!typeSel.value) return;

  Object.keys(GRANTS[typeSel.value]).forEach(g=>{
    grantSel.innerHTML += `<option>${g}</option>`;
  });

  grantSel.disabled = false;

  /* EXISTING LOGIC UNTOUCHED */
  if(typeSel.value==="TECH/ORD/ACSFP"){
    costBox.style.display="none";
    catBox.style.display="block";
  }else{
    costBox.style.display="block";
    catBox.style.display="none";
  }
};

grantSel.onchange = () => {

  subSel.innerHTML = '<option value="">-- Select --</option>';

  let data = GRANTS[typeSel.value][grantSel.value];

  if(Array.isArray(data) && data.length){
    data.forEach(s=>subSel.innerHTML+=`<option>${s}</option>`);
    subSel.disabled=false;
  }else{
    subSel.disabled=true;
  }

  // 🔥 CONDITION BASED LABEL CHANGE
  if(typeSel.value === "OPWKS" && grantSel.value === "OPWKS"){
      lpLabel.innerText = "JOB NO / NAR NO";
  }else{
      lpLabel.innerText = "LP NO";
  }
};
</script>

</body>
</html>
