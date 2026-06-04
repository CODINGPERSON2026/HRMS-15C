<?php
if (session_status() === PHP_SESSION_NONE) session_start();

if (empty($_SESSION['id'])) {
    header('location:logout.php');
    exit;
}

$_HDR_DASHBOARD = (isset($_SESSION['role']) && in_array($_SESSION['role'], ['admin','super_admin']))
    ? 'dboard.php'
    : 'user_dboard.php';

$_HDR_USERNAME = strtoupper($_SESSION['username'] ?? 'USER');
$_HDR_ROLE     = strtoupper($_SESSION['role'] ?? '');
?>
<link rel="stylesheet" href="assets\fontawesome-free-7.1.0-web\css\all.min.css">
<style>
/* =====================================================
   IMS-HEADER
   ===================================================== */
.ims-hdr *, .ims-hdr *::before, .ims-hdr *::after {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
}

.ims-hdr {
    position: relative;
    width: 100%;
    height: 90px;
    background: linear-gradient(135deg, #3c425c 0%, #764ba2 100%);
    border-bottom: 3px solid #c8a84b;
    display: flex;
    align-items: center;
    justify-content: center;   /* centres the inner wrapper */
    overflow: visible;
    font-family: 'Georgia', serif;
    z-index: 200;
}

/* diagonal texture */
.ims-hdr::before {
    content: '';
    position: absolute;
    inset: 0;
    background: repeating-linear-gradient(
        -55deg,
        rgba(255,255,255,0.015) 0px,
        rgba(255,255,255,0.015) 1px,
        transparent 1px,
        transparent 18px
    );
    pointer-events: none;
}

/* gold top line */
.ims-hdr::after {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0;
    height: 3px;
    background: linear-gradient(90deg,
        transparent 0%,
        #c8a84b 15%,
        #f5e09e 50%,
        #c8a84b 85%,
        transparent 100%
    );
}

/* ── width cap so it never over-spreads on large monitors ── */
.ims-hdr__inner {
    position: relative;
    z-index: 2;
    width: 100%;
    max-width: 1600px;
    padding: 0 28px;
    display: flex;
    align-items: center;
}

/* ===== LEFT: logo ===== */
.ims-hdr__left {
    flex: 0 0 auto;
    display: flex;
    align-items: center;
    justify-content: center;
    padding-right: 18px;
}

.ims-hdr__logo {
    height: 68px;
    width: 68px;
    object-fit: contain;
    border-radius: 50%;
    border: 2px solid rgba(200,168,75,0.6);
    background: rgba(255,255,255,0.05);
    display: block;
}

/* ===== CENTER: title ===== */
.ims-hdr__center {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 4px;
    padding: 0 16px;
    min-width: 0;
}

.ims-hdr__super {
    font-family: 'Trebuchet MS', sans-serif;
    font-size: 10px;
    font-weight: 800;
    letter-spacing: 4px;
    text-transform: uppercase;
    color:rgb(216, 209, 189);
}

.ims-hdr__title {
    font-family: 'Georgia', serif;
    font-size: 26px;
    font-weight: 700;
    color: #ffffff;            /* WHITE */
    letter-spacing: 2px;
    text-transform: uppercase;
    text-align: center;
    line-height: 1.1;
    white-space: nowrap;
}

.ims-hdr__title a {
    color: #ffffff;
    text-decoration: none;
    transition: color 0.2s;
}
.ims-hdr__title a:hover { color: #f5e09e; }

/* ===== RIGHT: user dropdown ===== */
.ims-hdr__right {
    flex: 0 0 auto;
    display: flex;
    align-items: center;
    justify-content: flex-end;
    padding-left: 18px;
    position: relative;
    z-index: 300;
}

.ims-hdr__user-btn {
    display: flex;
    align-items: center;
    gap: 8px;
    background: rgba(255,255,255,0.1);
    border: 1px solid rgba(200,168,75,0.5);
    border-radius: 30px;
    padding: 6px 14px 6px 10px;
    cursor: pointer;
    transition: background 0.2s;
    font-family: 'Trebuchet MS', sans-serif;
    user-select: none;
    white-space: nowrap;
}
.ims-hdr__user-btn:hover { background: rgba(255,255,255,0.18); }

.ims-hdr__avatar {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: #c8a84b;
    color: #1e1e2e;
    font-size: 12px;
    font-weight: 700;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    font-family: 'Trebuchet MS', sans-serif;
}

.ims-hdr__user-info {
    display: flex;
    flex-direction: column;
    line-height: 1.25;
}

.ims-hdr__user-name {
    font-size: 12px;
    font-weight: 700;
    color: #ffffff;            /* WHITE */
    max-width: 120px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.ims-hdr__user-role {
    font-size: 9px;
    color: #c8a84b;
    letter-spacing: 1px;
    text-transform: uppercase;
}

.ims-hdr__caret {
    font-size: 10px;
    color: #c8a84b;
    margin-left: 2px;
    transition: transform 0.2s;
}
.ims-hdr__caret.ims-open { transform: rotate(180deg); }

/* dropdown panel */
.ims-hdr__dropdown {
    display: none;
    position: absolute;
    top: calc(100% + 10px);
    right: 0;
    width: 195px;
    background: #1e1e2e;
    border: 1px solid rgba(200,168,75,0.35);
    border-radius: 14px;
    overflow: hidden;
    box-shadow: 0 12px 32px rgba(0,0,0,0.55);
    z-index: 500;
}
.ims-hdr__dropdown.ims-open { display: block; }

.ims-hdr__drop-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 13px 16px;
    font-family: 'Trebuchet MS', sans-serif;
    font-size: 12px;
    font-weight: 700;
    color: #ffffff;            /* WHITE */
    text-decoration: none;
    letter-spacing: 0.5px;
    transition: background 0.15s, color 0.15s;
    border-bottom: 1px solid rgba(255,255,255,0.06);
}
.ims-hdr__drop-item:last-child { border-bottom: none; }
.ims-hdr__drop-item:hover { text-decoration: none; }
.ims-hdr__drop-item--change:hover { background: #e65100; color: #fff; }
.ims-hdr__drop-item--logout:hover  { background: #c62828; color: #fff; }
.ims-hdr__drop-item i {
    font-size: 14px;
    width: 16px;
    text-align: center;
    color: #c8a84b;
}
.ims-hdr__drop-item:hover i { color: #fff; }

/* ===== RESPONSIVE ===== */
@media (max-width: 1024px) {
    .ims-hdr__title { font-size: 20px; }
}

@media (max-width: 768px) {
    .ims-hdr            { height: 70px; }
    .ims-hdr__title     { font-size: 15px; letter-spacing: 1px; }
    .ims-hdr__super     { font-size: 8px; letter-spacing: 2px; }
    .ims-hdr__logo      { height: 50px; width: 50px; }
    .ims-hdr__user-name { max-width: 70px; }
    .ims-hdr__inner     { padding: 0 14px; }
}

@media (max-width: 480px) {
    .ims-hdr__super     { display: none; }
    .ims-hdr__title     { font-size: 13px; letter-spacing: 0.5px; }
    .ims-hdr__logo      { height: 42px; width: 42px; }
    .ims-hdr__user-role { display: none; }
    .ims-hdr__inner     { padding: 0 10px; }
}
</style>

<div class="ims-hdr" role="banner">
    <div class="ims-hdr__inner">

        <!-- LEFT: logo -->
        <div class="ims-hdr__left">
            <img src="photos/chinar jimmy.png"
                 alt="15 Corps Engineering Signal Regiment Logo"
                 class="ims-hdr__logo">
        </div>

        <!-- CENTER: title -->
        <div class="ims-hdr__center">
            <span class="ims-hdr__super">15 Corps &mdash; Engineering Signal Regiment</span>
            <h1 class="ims-hdr__title">
                <a href="<?= htmlspecialchars($_HDR_DASHBOARD) ?>">Inventory Management System</a>
            </h1>
        </div>

        <!-- RIGHT: user dropdown -->
        <div class="ims-hdr__right">
            <div class="ims-hdr__user-btn" id="imsUserBtn">
                <div class="ims-hdr__avatar"><?= htmlspecialchars(substr($_HDR_USERNAME, 0, 2)) ?></div>
                <div class="ims-hdr__user-info">
                    <span class="ims-hdr__user-name"><?= htmlspecialchars($_HDR_USERNAME) ?></span>
                    <span class="ims-hdr__user-role"><?= htmlspecialchars($_HDR_ROLE) ?></span>
                </div>
                <i class="fa fa-chevron-down ims-hdr__caret" id="imsCaret"></i>
            </div>

            <div class="ims-hdr__dropdown" id="imsDropdown">
                <a href="change_password.php" class="ims-hdr__drop-item ims-hdr__drop-item--change">
                    <i class="fa fa-key"></i> CHANGE PASSWORD
                </a>
                <a href="logout.php" class="ims-hdr__drop-item ims-hdr__drop-item--logout">
                    <i class="fa fa-sign-out-alt"></i> LOGOUT
                </a>
            </div>
        </div>

    </div>
</div>

<script>
(function(){
    var btn   = document.getElementById('imsUserBtn');
    var drop  = document.getElementById('imsDropdown');
    var caret = document.getElementById('imsCaret');

    btn.addEventListener('click', function(e){
        e.stopPropagation();
        drop.classList.toggle('ims-open');
        caret.classList.toggle('ims-open');
    });

    document.addEventListener('click', function(){
        drop.classList.remove('ims-open');
        caret.classList.remove('ims-open');
    });

    drop.addEventListener('click', function(e){ e.stopPropagation(); });
})();
</script>