<?php
/* ================= STAT COUNTS ================= */
$dw_total_issued    = 0;
$dw_total_available = 0;

$dw_r = $connect->query("SELECT status, COUNT(*) AS c FROM equipment_items WHERE status IN ('ISSUED','AVAILABLE') GROUP BY status");
while ($dw_row = $dw_r->fetch_assoc()) {
    if ($dw_row['status'] === 'ISSUED')    $dw_total_issued    = (int)$dw_row['c'];
    if ($dw_row['status'] === 'AVAILABLE') $dw_total_available = (int)$dw_row['c'];
}

$dw_notifications = [];
while ($dw_n = $notifications->fetch_assoc()) {
    $dw_notifications[] = $dw_n;
}
$dw_notifications = array_slice($dw_notifications, 0, 10);
?>

<style>
/* ============================================================
   SHARED PAGE SHELL — one outer gutter for ALL dashboard rows
   Both dashboard_wrapper.php and wrapper-charts.php sit inside
   .dw-page, so their left/right edges always align perfectly.
   ============================================================ */
   .dw-page {
    width: 100%;
    margin-top: 15px;
    padding: clamp(10px, 1.5vw, 20px)
             clamp(12px, 1.8vw, 24px);
    box-sizing: border-box;
}

/* ── Each row is a full-width grid capped at 1600px ── */
.dw-row-shell {
    max-width: 1600px;
    width: 100%;
    margin: 0 auto;
}

/* ── Top row: stat cards + notification panel ── */
.dw-grid {
    display: grid;
    grid-template-columns: minmax(0, 560px) minmax(0, 1fr);
    gap: clamp(10px, 1.2vw, 18px);
    align-items: start;
}

/* ── Stat column: always 2 cards side-by-side ── */
.dw-stat-col {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: clamp(10px, 1.1vw, 16px);
}

.dw-stat-card {
    background: #fff;
    border-radius: clamp(12px, 1.2vw, 18px);
    padding: clamp(10px, 1.2vw, 16px) clamp(12px, 1.5vw, 18px);
    box-shadow: 0 6px 18px rgba(0,0,0,.10);
    display: flex;
    flex-direction: column;
    gap: 2px;
    position: relative;
    overflow: hidden;
    min-width: 0;
}
.dw-stat-card::after {
    content: '';
    position: absolute;
    right: -14px; bottom: -14px;
    width: 70px; height: 70px;
    border-radius: 50%;
    opacity: .07;
    background: currentColor;
}
.dw-stat-icon { font-size: clamp(16px, 1.4vw, 22px); margin-bottom: 2px; }

.dw-stat-row {
    display: flex;
    align-items: baseline;
    justify-content: space-between;
    gap: 8px;
    min-width: 0;
}
.dw-stat-label {
    font-size: clamp(10px, .85vw, 13px);
    font-weight: 700;
    letter-spacing: .6px;
    text-transform: capitalize;
    color: #222;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.dw-stat-value {
    font-size: clamp(20px, 2.2vw, 32px);
    font-weight: 700;
    line-height: 1;
    white-space: nowrap;
    flex-shrink: 0;
}
.dw-stat-sub {
    font-size: clamp(9px, .7vw, 11px);
    color: #aaa;
    margin-top: -4px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.dw-stat-card.dw-blue .dw-stat-icon,
.dw-stat-card.dw-blue .dw-stat-value { color: #1565c0; }
.dw-stat-card.dw-teal .dw-stat-icon,
.dw-stat-card.dw-teal .dw-stat-value { color: #00695c; }

/* ── Notification panel ── */
.dw-notif-panel {
    background: #fff;
    border-radius: clamp(12px, 1.2vw, 18px);
    box-shadow: 0 6px 18px rgba(0,0,0,.10);
    display: flex;
    flex-direction: column;
    overflow: hidden;
    box-sizing: border-box;
}
.dw-notif-track {
    flex: 1;
    overflow: hidden;
    position: relative;
}
.dw-notif-scroll {
    display: flex;
    align-items: stretch;
    gap: 12px;
    position: absolute;
    top: 0; left: 0; bottom: 0;
    
    animation: dw-scrollH 40s linear infinite;
    will-change: transform;
}
.dw-notif-scroll.dw-paused { animation-play-state: paused; }
@keyframes dw-scrollH {
    0%   { transform: translateX(0); }
    100% { transform: translateX(-50%); }
}
.dw-notif-card {
    flex-shrink: 0;
    width: 300px;
    box-sizing: border-box;
    background: #f9f9f9;
    border-left: 4px solid #8b0000;
    border-radius: 10px;
    padding: 12px 14px;
    display: flex;
    flex-direction: column;
    justify-content: center;
    gap: 5px;
    overflow: hidden;
    cursor: default;
    transition: box-shadow .2s, background .2s;
}
.dw-notif-card:hover { background: #f0f0f0; box-shadow: 0 4px 14px rgba(0,0,0,.10); }
.dw-notif-card.success { border-left-color: #4caf50; }
.dw-notif-card.warning { border-left-color: #ff9800; }
.dw-notif-card.danger  { border-left-color: #f44336; }
.dw-notif-card.info    { border-left-color: #2196f3; }

.dw-notif-new {
    font-size: 10px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 1.2px;
    flex-shrink: 0;
    color: red;
    animation: dw-pulse .8s ease-in-out infinite;
}
@keyframes dw-pulse {
    0%, 100% { opacity: 1; }
    50%       { opacity: .45; }
}
.dw-notif-title { font-size: 12px; font-weight: 700; color: #222; }
.dw-notif-msg   { font-size: 11px; color: #666; line-height: 1.5; }
.dw-notif-meta  {
    display: flex; align-items: center; gap: 5px;
    margin-top: 2px; font-size: 10px; color: #2196f3;
    flex-shrink: 0;
}
.dw-notif-meta i { font-size: 10px; }

/* ── Responsive ── */
@media (max-width: 900px) {
    .dw-grid { grid-template-columns: 1fr; }
    .dw-notif-panel { height: 160px !important; }
}
@media (max-width: 520px) {
    .dw-stat-col { grid-template-columns: 1fr; }
}
</style>

<!-- =====================================================
     PAGE SHELL — wraps BOTH includes with one gutter
     Open here, closed at the bottom of wrapper-charts.php
===================================================== -->
<div class="dw-page">

    <!-- ── ROW 1: Stat cards + Notifications ── -->
    <div class="dw-row-shell">
        <div class="dw-grid" id="dw-grid">

            <!-- LEFT: STAT CARDS -->
            <div class="dw-stat-col" id="dw-stat-col">

                <div class="dw-stat-card dw-blue">
                    <i class="fa fa-box-open dw-stat-icon"></i>
                    <div class="dw-stat-row">
                        <div class="dw-stat-label">Total Issued</div>
                        <div class="dw-stat-value" id="dw-v-issued">0</div>
                    </div>
                    <div class="dw-stat-sub">Equipment on loan</div>
                </div>

                <div class="dw-stat-card dw-teal">
                    <i class="fa fa-boxes dw-stat-icon"></i>
                    <div class="dw-stat-row">
                        <div class="dw-stat-label">Total Available</div>
                        <div class="dw-stat-value" id="dw-v-avail">0</div>
                    </div>
                    <div class="dw-stat-sub">Ready to issue</div>
                </div>

            </div>

            <!-- RIGHT: NOTIFICATIONS -->
            <div class="dw-notif-panel" id="dw-notif-panel">
                <div class="dw-notif-track">
                    <div class="dw-notif-scroll" id="dw-notif-scroll">

                        <?php if (!empty($dw_notifications)): ?>

                            <?php foreach ($dw_notifications as $dw_n): ?>
                                <div class="dw-notif-card <?= htmlspecialchars($dw_n['type']) ?>">
                                    <?php if ($dw_n['is_new'] == 1): ?>
                                        <span class="dw-notif-new">● NEW</span>
                                    <?php endif; ?>
                                    <div class="dw-notif-msg"><?= htmlspecialchars($dw_n['message']) ?></div>
                                    <div class="dw-notif-meta">
                                        <i class="fa fa-clock"></i>
                                        <?php
                                            $dw_diff = time() - strtotime($dw_n['created_at']);
                                            if     ($dw_diff < 60)    echo $dw_diff . 's ago';
                                            elseif ($dw_diff < 3600)  echo floor($dw_diff / 60) . 'm ago';
                                            elseif ($dw_diff < 86400) echo floor($dw_diff / 3600) . 'h ago';
                                            else                      echo date('d M', strtotime($dw_n['created_at']));
                                        ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>

                            <?php foreach ($dw_notifications as $dw_n): /* duplicate for seamless loop */ ?>
                                <div class="dw-notif-card <?= htmlspecialchars($dw_n['type']) ?>">
                                    <?php if ($dw_n['is_new'] == 1): ?>
                                        <span class="dw-notif-new">● NEW</span>
                                    <?php endif; ?>
                                    <div class="dw-notif-msg"><?= htmlspecialchars($dw_n['message']) ?></div>
                                    <div class="dw-notif-meta">
                                        <i class="fa fa-clock"></i>
                                        <?php
                                            $dw_diff = time() - strtotime($dw_n['created_at']);
                                            if     ($dw_diff < 60)    echo $dw_diff . 's ago';
                                            elseif ($dw_diff < 3600)  echo floor($dw_diff / 60) . 'm ago';
                                            elseif ($dw_diff < 86400) echo floor($dw_diff / 3600) . 'h ago';
                                            else                      echo date('d M', strtotime($dw_n['created_at']));
                                        ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>

                        <?php else: ?>
                            <div class="dw-notif-card">
                                <div class="dw-notif-title">No Recent Notifications</div>
                                <div class="dw-notif-msg">You are all caught up.</div>
                            </div>
                        <?php endif; ?>

                    </div>
                </div>
            </div>

        </div>
    </div><!-- /.dw-row-shell -->
    </div>
<script>
(function () {
    function dwCountUp(id, target, duration) {
        var el = document.getElementById(id);
        if (!el) return;
        var start = performance.now();
        function step(now) {
            var p    = Math.min((now - start) / duration, 1);
            var ease = 1 - Math.pow(1 - p, 3);
            el.textContent = Math.round(ease * target).toLocaleString();
            if (p < 1) requestAnimationFrame(step);
        }
        requestAnimationFrame(step);
    }
    dwCountUp('dw-v-issued', <?= (int)$dw_total_issued ?>,    1800);
    dwCountUp('dw-v-avail',  <?= (int)$dw_total_available ?>, 1800);

    function dwMatchHeight() {
        var col   = document.getElementById('dw-stat-col');
        var panel = document.getElementById('dw-notif-panel');
        if (!col || !panel) return;
        if (window.innerWidth > 900) {
            panel.style.height = col.offsetHeight + 'px';
        } else {
            panel.style.height = '160px';
        }
    }
    window.addEventListener('load',   dwMatchHeight);
    window.addEventListener('resize', dwMatchHeight);
    setTimeout(dwMatchHeight, 120);

    var dwScroll = document.getElementById('dw-notif-scroll');
    if (dwScroll) {
        dwScroll.addEventListener('mouseenter', function () { dwScroll.classList.add('dw-paused'); });
        dwScroll.addEventListener('mouseleave', function () { dwScroll.classList.remove('dw-paused'); });
    }
})();
</script>