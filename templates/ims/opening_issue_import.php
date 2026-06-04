<?php
session_start();
require_once "auth.php";
require_admin();
require_once "connect.php";
require_once "vendor/autoload.php";

use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

if (!isset($_POST['do_import'], $_SESSION['OPENING_ISSUE_EXCEL'])) {
    die("No Excel data found.");
}

$rows = $_SESSION['OPENING_ISSUE_EXCEL'];
unset($_SESSION['OPENING_ISSUE_EXCEL']);

$connect->begin_transaction();

try {

    /* ===============================
       PREPARED STATEMENTS
    =============================== */

    $stmtGrant = $connect->prepare("
        SELECT id FROM grants_master
        WHERE grant_type=? AND grant_name=?
        AND (sub_grant=? OR sub_grant IS NULL)
        LIMIT 1
    ");

    $stmtEquip = $connect->prepare("
        SELECT id FROM equipment_master
        WHERE grant_id=? AND equipment_name=? AND lp_no=?
        LIMIT 1
    ");

    $stmtInsertEquip = $connect->prepare("
        INSERT INTO equipment_master
        (grant_id, equipment_name, lp_no, cat_part_no, au,
         qty_received, qty_available, received_date, cost)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $stmtTxn = $connect->prepare("
        INSERT INTO equipment_txn
        (equipment_id, txn_type, qty, issue_date,
         issue_type, unit_name, remarks, created_by)
        VALUES (?, 'ISSUE', ?, ?, 'OPENING', ?, ?, ?)
    ");

    /* ===============================
       LOOP THROUGH EXCEL
    =============================== */

    foreach ($rows as $i => $row) {

        if ($i == 1) continue; // Skip header

        $grant_type = trim($row['A']);
        $grant_name = trim($row['B']);
        $sub_grant  = trim($row['C']);
        $equip_name = trim($row['D']);
        $lp_no      = trim($row['E']);
        $cat_no     = trim($row['F']);
        $au         = trim($row['G']);
        $unit       = strtoupper(trim($row['H']));
        $qty        = (int)$row['I'];
        $date_raw   = trim($row['J']);
        $cost       = (float)$row['K'];
        $remarks    = trim($row['L']);

        if ($qty <= 0) continue;

        /* ===============================
           ISSUE DATE FINAL SAFE FIX
        =============================== */

        $issue_date = NULL;

        if (!empty($date_raw)) {

            if (is_numeric($date_raw)) {
                $issue_date = date('Y-m-d', ExcelDate::excelToTimestamp($date_raw));
            } else {
                $temp = strtotime($date_raw);
                if ($temp !== false) {
                    $issue_date = date('Y-m-d', $temp);
                }
            }
        }

        /* ===============================
           GET GRANT ID
        =============================== */

        $stmtGrant->bind_param("sss", $grant_type, $grant_name, $sub_grant);
        $stmtGrant->execute();
        $g = $stmtGrant->get_result()->fetch_assoc();
        if (!$g) continue;
        $grant_id = $g['id'];

        /* ===============================
           CHECK EQUIPMENT EXISTS
        =============================== */

        $stmtEquip->bind_param("iss", $grant_id, $equip_name, $lp_no);
        $stmtEquip->execute();
        $e = $stmtEquip->get_result()->fetch_assoc();

        if (!$e) {

            $received_date = NULL; // IMPORTANT

            $stmtInsertEquip->bind_param(
                "issssissd",
                $grant_id,
                $equip_name,
                $lp_no,
                $cat_no,
                $au,
                $qty,
                $qty,
                $received_date,
                $cost
            );

            $stmtInsertEquip->execute();
            $equipment_id = $stmtInsertEquip->insert_id;

        } else {
            $equipment_id = $e['id'];
        }

        /* ===============================
           INSERT TRANSACTION
        =============================== */

        $stmtTxn->bind_param(
            "iisssi",
            $equipment_id,
            $qty,
            $issue_date,   // NULL properly insert hoga
            $unit,
            $remarks,
            $_SESSION['id']
        );

        $stmtTxn->execute();
    }

    $connect->commit();

} catch (Exception $e) {

    $connect->rollback();
    die("Import Failed: " . $e->getMessage());
}

header("Location: central_view.php?opening_done=1");
exit;
?>
