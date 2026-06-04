<?php
session_start();

/* ================= SECURITY HEADERS ================= */
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

require_once "auth.php";
require_admin();
require_once 'connect.php';

/* ================= ROLE GUARD ================= */
if (
    !isset($_SESSION['id']) ||
    !isset($_SESSION['role']) ||
    !in_array($_SESSION['role'], ['admin', 'super_admin'])
) {
    header('Location: logout.php');
    exit;
}

/* ================= REQUEST COUNT ================= */
$in_process_count = 0;
$stmt = $connect->prepare("
    SELECT COUNT(*)
    FROM users
    WHERE status IS NULL OR status='' OR status='PENDING'
");
$stmt->execute();
$stmt->bind_result($in_process_count);
$stmt->fetch();
$stmt->close();

/* ================= NOTIFICATION COUNT ================= */
$notif_count = 0;
$r = $connect->query("SELECT COUNT(*) AS c FROM notifications WHERE is_read=0");
if ($row = $r->fetch_assoc())
    $notif_count = $row['c'];

/* ================= NOTIFICATIONS ================= */
$notifications = $connect->query("
    SELECT *,
    CASE WHEN created_at >= NOW() - INTERVAL 1 DAY THEN 1 ELSE 0 END AS is_new
    FROM notifications
    WHERE created_at >= NOW() - INTERVAL 1 DAY
    ORDER BY created_at DESC
");

/* ================= MENUS ================= */
$tech_grants = [
    'ORD' => [
        'TECH' => 'tech_grant/ord_technical.php',
        'GEN'  => 'tech_grant/ord_general.php',
        'ARMS' => 'tech_grant/ord_arms.php'
    ],
    'ACSFP' => 'tech_grant/acsfp_grant.php',
    'SECT'  => 'tech_grant/sect_grant.php',
    'LOAN'  => [
        'TECH'  => 'tech_grant/loan_ord_technical.php',
        'GEN'   => 'tech_grant/loan_ord_general.php',
        'ARMS'  => 'tech_grant/loan_ord_arms.php',
        'ACSFP' => 'tech_grant/loan_acsfp.php',
        'SECT'  => 'tech_grant/loan_sect.php'
    ]
];
$public_grants = [
    'ACG GRANT'    => 'public_grant/acg_grant.php',
    'ATG GRANT'    => 'public_grant/atg_grant.php',
    'AMENITY GRANT'=> 'public_grant/amenity_grant.php',
    'ETG GRANT'    => 'public_grant/etg_grant.php'
];
$private_grants = [
    'REGT FUND'     => 'private_grant/regt_grant.php',
    'CSD FUND'      => 'private_grant/csd_grant.php',
    'OFFR MESS FUND'=> 'private_grant/offr_grant.php'
];
?>
<!DOCTYPE html>
<html>
<head>
    <title>ADMIN DASHBOARD</title>
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="assets\fontawesome-free-7.1.0-web\css\all.min.css">
    <style>
        /* ===== RESET & BASE ===== */
        html, body {
            height: 100%;
            margin: 0;
        }
        body {
            display: flex;
            flex-direction: column;
            
            font-family: 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #e3f2fd, #c8e6c9);
        }
        a {
            text-decoration: none;
            color: #000;
        }

        /* ===================================================
           WHITE TOPBAR
        =================================================== */
        .topbar {
    height: 46px;
    background: #ffffff;
    border-bottom: 1px solid #e0e0e0;
    box-shadow: 0 2px 8px rgba(0,0,0,.08);
    display: flex;
    align-items: center;
    justify-content: flex-start;   /* ← key fix */
    padding: 0 12px;
    gap: 6px;
    position: relative;
    z-index: 199;
    flex-wrap: nowrap;
    overflow: visible;
}

        /* Clock */
        #dateTimeDisplay {
            font-size: 13px;
            font-weight: 600;
            color: #555;
            white-space: nowrap;
            margin-left: auto;
            order: 99;
        }

        /* Quick-action buttons */
        .topbar-btn {
    flex-shrink: 0;
    flex-grow: 0;    /* ← key fix */
    display: flex;
    align-items: center;
    gap: 4px;
    height: 30px;
    padding: 0 10px;
    border-radius: 6px;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: .3px;
    border: none;
    cursor: pointer;
    transition: background .2s, transform .15s;
    white-space: nowrap;
    color: #fff;
    text-decoration: none;
}
        .topbar-btn:hover { transform: translateY(-1px); color: #fff; }
        .btn-green  { background: #2e7d32; }
        .btn-green:hover  { background: #1b5e20; }
        .btn-blue   { background: #1565c0; }
        .btn-blue:hover   { background: #0d47a1; }
        .btn-teal   { background: #00695c; }
        .btn-teal:hover   { background: #004d40; }
        .btn-orange { background: #e65100; }
        .btn-orange:hover { background: #bf360c; }
        .btn-red    { background: #c62828; }
        .btn-red:hover    { background: #8b0000; }
        .btn-purple { background: #6a1b9a; }
        .btn-purple:hover { background: #4a148c; }

        /* Nested flyout menus */
        .more-nested-wrap { position: relative; }
        .more-menu-parent {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 10px 16px;
            font-size: 12px;
            font-weight: 700;
            color: #333;
            cursor: pointer;
            border-bottom: 1px solid #f0f0f0;
            transition: background .15s;
            user-select: none;
        }
        .more-menu-parent:hover { background: #f0f4ff; color: #1a237e; }
        .more-nested-wrap.open > .more-menu-parent { background: #e8eaf6; color: #1a237e; }
        .more-nested-menu {
            display: none;
            position: absolute;
            top: 0;
            left: 100%;
            min-width: 200px;
            background: #fff;
            border: 1px solid #dde2f0;
            border-radius: 10px;
            box-shadow: 0 10px 28px rgba(0,0,0,.18);
            z-index: 1200;
            overflow: visible;
        }
        .more-nested-wrap.open > .more-nested-menu { display: block; }
        .more-nested-menu a {
            display: flex;
            align-items: center;
            padding: 10px 14px;
            font-size: 12px;
            font-weight: 600;
            color: #333;
            border-bottom: 1px solid #f0f0f0;
            transition: background .15s;
        }
        .more-nested-menu a:last-child { border-bottom: none; }
        .more-nested-menu a:hover { background: #e8f5e9; color: #1b5e20; }

        /* "More" dropdown button */
        .more-wrap { position: relative; }
        .btn-more { background: #37474f; }
        .btn-more:hover { background: #263238; }
        .more-menu {
            display: none;
            position: absolute;
            top: calc(100% + 8px);
            right: 0;
            width: 220px;
            background: #fff;
            border: 1px solid #e0e0e0;
            border-radius: 12px;
            box-shadow: 0 10px 28px rgba(0,0,0,.15);
            overflow: visible;
            z-index: 999;
        }
        .more-menu.open { display: block; }
        .more-menu a {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 11px 16px;
            font-size: 12px;
            font-weight: 700;
            color: #333;
            border-bottom: 1px solid #f0f0f0;
            transition: background .15s;
        }
        .more-menu a:last-child { border-bottom: none; }
        .more-menu a i { width: 16px; text-align: center; color: #555; }
        .more-menu a:hover { background: #f5f5f5; color: #000; }
        .more-menu-divider { height: 1px; background: #e0e0e0; margin: 4px 0; }

        /* Bell */
        .bell-wrap {
            position: relative;
            cursor: pointer;
            color: #444;
            font-size: 20px;
            display: flex;
            align-items: center;
            padding: 4px 8px;
            border-radius: 8px;
            transition: background .15s;
        }
        .bell-wrap:hover { background: #f0f0f0; }
        .bell-badge {
            position: absolute;
            top: 0px;
            right: 2px;
            background: #e53935;
            color: #fff;
            font-size: 9px;
            font-weight: 700;
            padding: 1px 5px;
            border-radius: 20px;
            line-height: 1.4;
        }

        /* ===== FOOTER ===== */
        .footer {
            height: 45px;
            background: #004ea8;
            color: #fff;
            display: flex;
            align-items: center;
            overflow: hidden;
            font-size: 18px;
        }
        .ticker {
            white-space: nowrap;
            display: inline-block;
            padding-left: 100%;
            animation: scrollText 25s linear infinite;
            font-weight: 700;
        }
        @keyframes scrollText {
            0%   { transform: translateX(0); }
            100% { transform: translateX(-100%); }
        }
        .dwcost-section{
    margin-top: 40px;
}
    </style>
</head>
<body>

    <?php include 'header.php'; ?>

    <!-- ===================================================
         WHITE TOPBAR
    =================================================== -->
    <div class="topbar">

        <a href="add_central.php" class="topbar-btn btn-green">
            <i class="fa fa-plus"></i> ADD EQUIPMENT
        </a>

        <a href="central_view.php" class="topbar-btn btn-blue">
            <i class="fa fa-box-open"></i> NEW ARRIVALS
        </a>

        <a href="central_report.php" class="topbar-btn btn-teal">
            <i class="fa fa-file-alt"></i> EQP REPORT
        </a>

       

        <!-- GRANTS dropdown -->
        <div class="more-wrap" id="grantsWrap">
            <button class="topbar-btn btn-purple" id="grantsBtn" onclick="toggleGrants(event)">
                <i class="fa fa-layer-group"></i> GRANTS <i class="fa fa-chevron-down" style="font-size:10px;margin-left:2px;"></i>
            </button>
            <div class="more-menu" id="grantsMenu" style="width:260px;">

                <!-- TECH / ORD / ACSFP -->
                <div class="more-nested-wrap">
                    <div class="more-menu-parent" onclick="toggleMoreNested(event,this)">
                        <span><i class="fa fa-microchip" style="width:16px;text-align:center;margin-right:10px;color:#555;"></i> TECH / ORD / ACSFP</span>
                        <i class="fa fa-chevron-right" style="font-size:10px;color:#aaa;"></i>
                    </div>
                    <div class="more-nested-menu">
                        <?php foreach ($tech_grants as $name => $value): ?>
                            <?php if (is_array($value)): ?>
                                <div class="more-nested-wrap lv2">
                                    <div class="more-menu-parent" onclick="toggleMoreNested(event,this)">
                                        <span>▶ <?= $name ?></span>
                                        <i class="fa fa-chevron-right" style="font-size:10px;color:#aaa;"></i>
                                    </div>
                                    <div class="more-nested-menu">
                                        <?php foreach ($value as $k => $v): ?>
                                            <a href="<?= $v ?>"><?= $k ?></a>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php else: ?>
                                <a href="<?= $value ?>"><?= $name ?></a>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="more-menu-divider"></div>

                <!-- PUBLIC GRANT -->
                <div class="more-nested-wrap">
                    <div class="more-menu-parent" onclick="toggleMoreNested(event,this)">
                        <span><i class="fa fa-landmark" style="width:16px;text-align:center;margin-right:10px;color:#555;"></i> PUBLIC GRANT</span>
                        <i class="fa fa-chevron-right" style="font-size:10px;color:#aaa;"></i>
                    </div>
                    <div class="more-nested-menu">
                        <?php foreach ($public_grants as $name => $url): ?>
                            <a href="<?= $url ?>"><?= $name ?></a>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="more-menu-divider"></div>

                <!-- REGTL PROPERTIES -->
                <div class="more-nested-wrap">
                    <div class="more-menu-parent" onclick="toggleMoreNested(event,this)">
                        <span><i class="fa fa-building" style="width:16px;text-align:center;margin-right:10px;color:#555;"></i> REGTL PROPERTIES</span>
                        <i class="fa fa-chevron-right" style="font-size:10px;color:#aaa;"></i>
                    </div>
                    <div class="more-nested-menu">
                        <?php foreach ($private_grants as $name => $url): ?>
                            <a href="<?= $url ?>"><?= $name ?></a>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="more-menu-divider"></div>

                <!-- NFS STORE -->
                <div class="more-nested-wrap">
                    <div class="more-menu-parent" onclick="toggleMoreNested(event,this)">
                        <span><i class="fa fa-store" style="width:16px;text-align:center;margin-right:10px;color:#555;"></i> NFS STORE</span>
                        <i class="fa fa-chevron-right" style="font-size:10px;color:#aaa;"></i>
                    </div>
                    <div class="more-nested-menu">
                        <a href="private_grant/nfs_store/nfs_category_view.php?cat=GOFNMS">GOFNMS</a>
                        <a href="private_grant/nfs_store/nfs_category_view.php?cat=IPMPLS">IPMPLS</a>
                        <a href="private_grant/nfs_store/nfs_category_view.php?cat=DWDM">DWDM</a>
                        <a href="private_grant/nfs_store/nfs_category_view.php?cat=MW">MW</a>
                        <a href="private_grant/nfs_store/nfs_category_view.php?cat=STATIC SATL">STATIC SATL</a>
                        <a href="private_grant/nfs_store/nfs_category_view.php?cat=PORTABLE SATL">PORTABLE SATL</a>
                        <a href="private_grant/nfs_store/nfs_category_view.php?cat=HC-MCEU">HC-MCEU</a>
                        <a href="private_grant/nfs_store/nfs_category_view.php?cat=LC-MCEU">LC-MCEU</a>
                        <a href="private_grant/nfs_store/nfs_category_view.php?cat=OFC">OFC</a>
                    </div>
                </div>

            </div>
        </div>

        <!-- More dropdown -->
        <div class="more-wrap" id="moreWrap">
            <button class="topbar-btn btn-more" id="moreBtn" onclick="toggleMore(event)">
                <i class="fa fa-th-large"></i> MORE <i class="fa fa-chevron-down" style="font-size:10px;margin-left:2px;"></i>
            </button>
            <div class="more-menu" id="moreMenu">
                  <!-- APPROVALS -->
    <a href="user1.php">
        <i class="fa fa-inbox"></i> Request Approvals
        <?php if ($in_process_count > 0): ?>
            <span style="margin-left:auto;background:#c62828;color:#fff;padding:1px 6px;border-radius:10px;font-size:10px;">
                <?= $in_process_count ?>
            </span>
        <?php endif; ?>
    </a>

    <a href="admin_return_approval.php">
        <i class="fa fa-undo-alt"></i> Return Approvals
    </a>
                <a href="report_store_date_wise.php"><i class="fa fa-file-export"></i> Issue Report</a>
                <a href="voucher_list.php"><i class="fa fa-receipt"></i> Voucher Details</a>
                <a href="view_return_voucher.php"><i class="fa fa-file-invoice"></i> Return Vouchers</a>
                <a href="board_list.php"><i class="fa fa-clipboard-list"></i> ASTB Board</a>
                <a href="opwks.php"><i class="fa fa-tools"></i> OPWKS</a>
                <a href="loginactivity.php"><i class="fa fa-history"></i> Login Activity</a>
                <div class="more-menu-divider"></div>
                <a href="change_password.php"><i class="fa fa-key"></i> Change Password</a>
                <a href="logout.php" style="color:#e53935;"><i class="fa fa-sign-out-alt" style="color:#e53935;"></i> Logout</a>
            </div>
        </div>

        <!-- Clock -->
        <span id="dateTimeDisplay"></span>

        <!-- Bell -->
        <div class="bell-wrap" title="Notifications">
            <i class="fa fa-bell"></i>
            <?php if ($notif_count > 0): ?>
                <span class="bell-badge"><?= $notif_count ?></span>
            <?php endif; ?>
        </div>

    </div>


    <!-- ===================================================
         MAIN DASHBOARD — included from dashboard_wrapper.php
    =================================================== -->
    <?php include 'main-dashboard/dashboard_wrapper.php'; ?>
    <?php include 'main-dashboard/wrapper-charts.php'; ?>
    <div class="dwcost-section">
    <?php include 'main-dashboard/cost-charts.php'; ?>
</div>

    <!-- FOOTER
    <div class="footer">
        <div class="ticker">
            DESIGN AND DEVELOPED BY 15 CESR &nbsp;|&nbsp;
            THIS SITE IS UNDER CONTINUOUS UPDATION &nbsp;|&nbsp;
            CONTACT : 22015111
        </div>
    </div> -->

    <?php
    $connect->query("UPDATE notifications SET is_read=1 WHERE is_read=0");
    ?>

    <script>
        /* ===== CLOCK ===== */
        function updateClock() {
            document.getElementById("dateTimeDisplay").innerHTML = new Date().toLocaleString();
        }
        setInterval(updateClock, 1000);
        updateClock();

        /* ===== GRANTS DROPDOWN ===== */
        function toggleGrants(e) {
            e.stopPropagation();
            var gm = document.getElementById('grantsMenu');
            var wasOpen = gm.classList.contains('open');
            closeAll();
            if (!wasOpen) gm.classList.add('open');
        }

        /* ===== MORE DROPDOWN ===== */
        function toggleMore(e) {
            e.stopPropagation();
            var mm = document.getElementById('moreMenu');
            var wasOpen = mm.classList.contains('open');
            closeAll();
            if (!wasOpen) mm.classList.add('open');
        }

        /* ===== NESTED FLYOUTS ===== */
        function toggleMoreNested(e, el) {
            e.stopPropagation();
            var wrap   = el.parentElement;
            var isOpen = wrap.classList.contains('open');
            var siblings = wrap.parentElement.children;
            for (var i = 0; i < siblings.length; i++) {
                if (siblings[i] !== wrap && siblings[i].classList.contains('more-nested-wrap')) {
                    siblings[i].classList.remove('open');
                    siblings[i].querySelectorAll('.more-nested-wrap').forEach(function(d) { d.classList.remove('open'); });
                }
            }
            wrap.classList.toggle('open', !isOpen);
        }

        function closeAll() {
            document.getElementById('moreMenu').classList.remove('open');
            document.getElementById('grantsMenu').classList.remove('open');
            document.querySelectorAll('.more-nested-wrap').forEach(function(w) { w.classList.remove('open'); });
        }

        document.addEventListener('click', closeAll);
        document.getElementById('moreMenu').addEventListener('click',   function(e) { e.stopPropagation(); });
        document.getElementById('grantsMenu').addEventListener('click', function(e) { e.stopPropagation(); });

        /* ===== BACK BUTTON BLOCK ===== */
        (function() {
            history.pushState(null, null, location.href);
            window.onpopstate = function() { history.go(1); };
        })();

        /* ===== DISABLE PAGE CACHE ===== */
        window.addEventListener("pageshow", function(event) {
            if (event.persisted) window.location.reload();
        });
    </script>
</body>
</html>