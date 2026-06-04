<?php
session_start();
require_once "auth.php";
require_admin();
require_once "connect.php";

/* ===== SAFETY CHECK ===== */
if (
    !isset($_SESSION['OPENING_ISSUE_EXCEL']) ||
    !isset($_SESSION['OPENING_ISSUE_GRANT_ID'])
) {
    die("Invalid request");
}

$rows     = $_SESSION['OPENING_ISSUE_EXCEL'];
$grant_id = (int)$_SESSION['OPENING_ISSUE_GRANT_ID'];

/* ===== FETCH GRANT NAME (FOR CATEGORY VIEW) ===== */
$gRes = mysqli_query(
    $connect,
    "SELECT grant_name
     FROM grants_master
     WHERE id = $grant_id
       AND grant_type = 'NFS'
     LIMIT 1"
);

$gRow = mysqli_fetch_assoc($gRes);
if (!$gRow) {
    die("Invalid grant");
}

$category = mysqli_real_escape_string($connect, $gRow['grant_name']);

mysqli_begin_transaction($connect);

try {

    foreach ($rows as $i => $r) {

        if ($i == 1) continue; // skip header

        /*
        Excel Columns:
        A = Tender / Category (text)
        B = Equipment Name
        C = A/U
        D = Qty Received
        E = Qty Issued
        F = Received Date
        G = Issued Date
        H = Unit Cost
        I = Remarks
        */

        $equip = mysqli_real_escape_string($connect, trim($r['B']));
        $au    = mysqli_real_escape_string($connect, trim($r['C']));

        $qtyRec = (int)$r['D'];
        $qtyIss = (int)$r['E'];

        if ($equip === '' || $qtyRec <= 0) continue;

        $recDate = !empty($r['F']) ? $r['F'] : date('Y-m-d');
        $issDate = !empty($r['G']) ? $r['G'] : $recDate;
        $cost    = is_numeric($r['H']) ? (float)$r['H'] : 0;

        $remarks = mysqli_real_escape_string(
            $connect,
            trim($r['A']) . ' | ' . (trim($r['I']) ?: 'Opening issued before system')
        );

        /* ===== CHECK EXISTING EQUIPMENT ===== */
        $chk = mysqli_query($connect,"
            SELECT id
            FROM equipment_master
            WHERE grant_id = $grant_id
              AND equipment_name = '$equip'
            LIMIT 1
        ");

        if ($rowEq = mysqli_fetch_assoc($chk)) {

            $equipment_id = (int)$rowEq['id'];

        } else {

            $q1 = mysqli_query($connect,"
                INSERT INTO equipment_master
                (grant_id, category, equipment_name, au,
                 qty_received, qty_available, received_date, cost)
                VALUES
                ($grant_id,
                 '$category',
                 '$equip',
                 '$au',
                 $qtyRec,
                 0,
                 '$recDate',
                 $cost
                )
            ");

            if (!$q1) {
                throw new Exception(mysqli_error($connect));
            }

            $equipment_id = mysqli_insert_id($connect);
        }

        /* ===== OPENING ISSUE (SYSTEM COMPATIBLE) ===== */
        if ($qtyIss > 0) {

            $q2 = mysqli_query($connect,"
                INSERT INTO equipment_txn
                (equipment_id, txn_type, qty, issue_date,
                 issue_type, unit_name, remarks, created_at)
                VALUES
                ($equipment_id,
                 'ISSUE',
                 $qtyIss,
                 '$issDate',
                 'OPENING',
                 'NFS STORE',
                 '$remarks',
                 NOW()
                )
            ");

            if (!$q2) {
                throw new Exception(mysqli_error($connect));
            }
        }
    }

    mysqli_commit($connect);

    /* ===== CLEAR SESSION ===== */
    unset($_SESSION['OPENING_ISSUE_EXCEL']);
    unset($_SESSION['OPENING_ISSUE_GRANT_ID']);

    header("Location: nfs_opening_issue_upload.php?success=1");
    exit;

} catch (Exception $e) {

    mysqli_rollback($connect);
    die("Import failed ❌ : " . $e->getMessage());
}
