```php
<?php
session_start();
require_once "auth.php";
require_admin();
require_once "connect.php";
require_once "audit_log.php";

/* ================= ADMIN GUARD ================= */
if (
    !isset($_SESSION['id']) ||
    !in_array($_SESSION['role'], ['admin','super_admin'])
) {
    header("location:logout.php");
    exit;
}

$admin_id   = $_SESSION['id'];
$username   = $_SESSION['username'];
$request_id = intval($_GET['id'] ?? 0);
$action     = $_GET['action'] ?? '';

if ($request_id <= 0 || !in_array($action, ['approve','reject'])) {
    die("Invalid Request");
}

$connect->begin_transaction();

try {

    /* ================= FETCH RETURN REQUEST ================= */
    $q = $connect->prepare("
        SELECT
            rr.id AS request_id,
            rr.equipment_id,
            rr.return_qty,
            rr.store_name,
            rr.requested_by,

            em.lp_no,
            em.cat_part_no,
            em.equipment_name,
            em.au,
            em.qty_received,
            em.qty_available,

            gm.grant_name
        FROM return_requests rr
        JOIN equipment_master em ON em.id = rr.equipment_id
        JOIN grants_master gm ON gm.id = rr.grant_id
        WHERE rr.id = ?
          AND rr.status = 'PENDING'
        FOR UPDATE
    ");
    $q->bind_param("i", $request_id);
    $q->execute();
    $r = $q->get_result()->fetch_assoc();

    if (!$r) {
        throw new Exception("Invalid or already processed request");
    }

    /* =======================================================
       REJECT FLOW
       ======================================================= */
    if ($action === 'reject') {

        $rej = $connect->prepare("
            UPDATE return_requests
            SET status = 'REJECTED',
                approved_by = ?
            WHERE id = ?
        ");
        $rej->bind_param("ii", $admin_id, $request_id);
        $rej->execute();

        /* CREATE REJECTED RETURN VOUCHER */
        $rv_no = 'RV-' . date('YmdHis');

        $insVoucher = $connect->prepare("
            INSERT INTO return_vouchers
            (rv_no, rv_date, returned_from, auth_text, note, created_by, request_id, status)
            VALUES (?, CURDATE(), ?, '', 'Rejected Return', ?, ?, 'REJECTED')
        ");

        $insVoucher->bind_param(
            "ssii",
            $rv_no,
            $r['store_name'],
            $r['requested_by'],
            $request_id
        );
        $insVoucher->execute();
        $voucher_id = $connect->insert_id;

        $remarks = "REJECTED {$r['return_qty']} ITEMS";

        $insItem = $connect->prepare("
            INSERT INTO return_vouchers_items
            (
                return_voucher_id,
                equipment_id,
                lp_no,
                cat_part_no,
                nomenclature,
                au,
                qty,
                remarks
            )
            VALUES (?,?,?,?,?,?,?,?)
        ");

$insItem->bind_param(
    "iissssis",
    $voucher_id,
    $r['equipment_id'],
    $r['lp_no'],
    $r['cat_part_no'],
    $r['equipment_name'],
    $r['au'],
    $r['return_qty'],
    $remarks
);
$insItem->execute();

        audit_log(
            $connect,
            $admin_id,
            $username,
            'REJECT',
            'RETURN_REQUEST',
            $request_id,
            "Rejected return of {$r['equipment_name']} | Qty {$r['return_qty']}"
        );

        $connect->commit();
        header("Location: print_return_voucher.php?id=".$voucher_id);
        exit;
    }

    /* =======================================================
   APPROVE FLOW
   ======================================================= */

    $equipment_id = $r['equipment_id'];
    $return_qty   = $r['return_qty'];
    $store        = $r['store_name'];

    /* INSERT RETURN TRANSACTION */
    $insTxn = $connect->prepare("
        INSERT INTO equipment_txn
        (equipment_id, txn_type, qty, unit_name, created_at)
        VALUES (?, 'RETURN', ?, ?, NOW())
    ");
    $insTxn->bind_param("iis", $equipment_id, $return_qty, $store);
    $insTxn->execute();

    /* CREATE APPROVED RETURN VOUCHER */
    $rv_no = 'RV-' . date('YmdHis');

    $insVoucher = $connect->prepare("
        INSERT INTO return_vouchers
        (rv_no, rv_date, returned_from, auth_text, note, created_by, request_id, status)
        VALUES (?, CURDATE(), ?, '', '', ?, ?, 'APPROVED')
    ");

    $insVoucher->bind_param(
        "ssii",
        $rv_no,
        $store,
        $r['requested_by'],
        $request_id
    );
    $insVoucher->execute();

    $voucher_id = $connect->insert_id;

    /* INSERT VOUCHER ITEM */
    $remarks = "APPROVED {$return_qty} ITEMS";

    $insItem = $connect->prepare("
        INSERT INTO return_vouchers_items
        (
            return_voucher_id,
            equipment_id,
            lp_no,
            cat_part_no,
            nomenclature,
            au,
            qty,
            remarks
        )
        VALUES (?,?,?,?,?,?,?,?)
    ");
    $insItem->bind_param(
        "iissssis",
        $voucher_id,
        $equipment_id,
        $r['lp_no'],
        $r['cat_part_no'],
        $r['equipment_name'],
        $r['au'],
        $return_qty,
        $remarks
    );
    $insItem->execute();

    /* UPDATE REQUEST STATUS */
    $updReq = $connect->prepare("
        UPDATE return_requests
        SET status = 'APPROVED',
            approved_by = ?
        WHERE id = ?
    ");
    $updReq->bind_param("ii", $admin_id, $request_id);
    $updReq->execute();


    /* 4. CREATE APPROVED RETURN VOUCHER */
    $rv_no = 'RV-' . date('YmdHis');

    $insVoucher = $connect->prepare("
        INSERT INTO return_vouchers
        (rv_no, rv_date, returned_from, auth_text, note, created_by, request_id, status)
        VALUES (?, CURDATE(), ?, '', '', ?, ?, 'APPROVED')
    ");

    $insVoucher->bind_param(
        "ssii",
        $rv_no,
        $store,
        $r['requested_by'],
        $request_id
    );
    $insVoucher->execute();

    $voucher_id = $connect->insert_id;

    /* 5. INSERT VOUCHER ITEM */
    $remarks = "APPROVED {$return_qty} ITEMS";

    $insItem = $connect->prepare("
        INSERT INTO return_vouchers_items
        (
            return_voucher_id,
            equipment_id,
            lp_no,
            cat_part_no,
            nomenclature,
            au,
            qty,
            remarks
        )
        VALUES (?,?,?,?,?,?,?,?)
    ");
    $insItem->bind_param(
        "iissssis",
        $voucher_id,
        $equipment_id,
        $r['lp_no'],
        $r['cat_part_no'],
        $r['equipment_name'],
        $r['au'],
        $return_qty,
        $remarks
    );
    $insItem->execute();

    /* 6. UPDATE REQUEST STATUS */
    $updReq = $connect->prepare("
        UPDATE return_requests
        SET status = 'APPROVED',
            approved_by = ?
        WHERE id = ?
    ");
    $updReq->bind_param("ii", $admin_id, $request_id);
    $updReq->execute();

    /* 7. AUDIT LOG */
    audit_log(
        $connect,
        $admin_id,
        $username,
        'APPROVE',
        'RETURN_REQUEST',
        $request_id,
        "Approved return of {$r['equipment_name']} | Qty {$return_qty}"
    );

    $connect->commit();

    header("Location: print_return_voucher.php?id=".$voucher_id);
    exit;

} catch (Exception $e) {
    $connect->rollback();
    die("ERROR: " . $e->getMessage());
}
?>
```
