<?php
/* ===============================================================
   wrapper-charts.php  —  Grant Donut + Equipment Bar charts
   Included AFTER dashboard_wrapper.php.
   NOTE: dashboard_wrapper.php opens <div class="dw-page"> —
         this file adds ROW 2 and then closes </div>.
   =============================================================== */

/* ── Donut: Grant breakdown from DB ── */
$_dwc_color_palette = [
    '#1565c0', '#00695c', '#6a1b9a', '#e65100',
    '#b71c1c', '#2e7d32', '#f9a825', '#4527a0',
    '#00838f', '#ad1457',
];

$dw_grants      = [];
$dw_grant_total = 0;

$_dwc_r = $connect->query("
    SELECT
        gm.grant_type,
        COUNT(em.id) AS total_equipment_items
    FROM equipment_master em
    JOIN grants_master gm ON em.grant_id = gm.id
    GROUP BY gm.grant_type
    ORDER BY gm.grant_type
");

if ($_dwc_r) {
    $_dwc_i = 0;
    while ($_dwc_row = $_dwc_r->fetch_assoc()) {
        $dw_grants[] = [
            'label' => strtoupper($_dwc_row['grant_type']),
            'value' => (int)$_dwc_row['total_equipment_items'],
            'color' => $_dwc_color_palette[$_dwc_i % count($_dwc_color_palette)],
        ];
        $_dwc_i++;
    }
    $dw_grant_total = array_sum(array_column($dw_grants, 'value'));
}

/* ── Bar: Monthly Equipment Issues (dummy data for now) ── */
$dw_bar_data = [];

/* Create last 12 months with zero values */
for ($i = 11; $i >= 0; $i--) {
    $monthKey = date('M', strtotime("-{$i} months"));
    $dw_bar_data[$monthKey] = 0;
}

$barQuery = "
    SELECT
        DATE_FORMAT(created_at, '%b') AS month_name,
        DATE_FORMAT(created_at, '%Y-%m') AS month_sort,
        COUNT(*) AS total_items
    FROM equipment_master
    WHERE created_at >= DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 11 MONTH), '%Y-%m-01')
    GROUP BY month_sort, month_name
    ORDER BY month_sort
";

$barResult = $connect->query($barQuery);

if ($barResult) {
    while ($row = $barResult->fetch_assoc()) {
        $dw_bar_data[$row['month_name']] = (int)$row['total_items'];
    }
}

$dw_bar_max = max($dw_bar_data ?: [1]);
?>

<style>
/* ============================================================
   CHART ROW — ROW 2 inside .dw-page
   Uses same .dw-row-shell as the top row for perfect alignment.
   ============================================================ */

.dwc-row-gap { margin-top: clamp(10px, 1.2vw, 18px); }

.dwc-grid {
    display: grid;
    grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
    gap: clamp(10px, 1.2vw, 18px);
}

@keyframes dwc-fadeUp {
    from { opacity: 0; transform: translateY(20px); }
    to   { opacity: 1; transform: translateY(0);    }
}

.dwc-card {
    background: #fff;
    border-radius: clamp(12px, 1.2vw, 18px);
    box-shadow: 0 6px 18px rgba(0,0,0,.10);
    padding: clamp(14px, 1.4vw, 22px) clamp(16px, 1.6vw, 26px);
    display: flex;
    flex-direction: column;
    gap: clamp(10px, 1vw, 16px);
    min-width: 0;
    overflow: hidden;
    
}


.dwc-title {
    font-size: clamp(10px, .85vw, 13px);
    font-weight: 800;
    letter-spacing: .7px;
    text-transform: uppercase;
    color: #222;
    border-left: 4px solid #1565c0;
    padding-left: 10px;
    line-height: 1.2;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.dwc-title.dwc-bar-title { border-left-color: #00695c; }

/* ── DONUT ── */
.dwc-donut-wrap {
    display: flex;
    align-items: center;
    justify-content: center;
    flex-wrap: nowrap;
    width: 100%;
    min-width: 0;
    gap: clamp(8px, 1vw, 16px);
}

.dwc-donut-svg-wrap {
    position: relative;
    flex-shrink: 0;
    width:  clamp(130px, 13vw, 200px);
    height: clamp(130px, 13vw, 200px);
}
.dwc-donut-svg-wrap svg { display: block; width: 100%; height: 100%; }

.dwc-donut-center {
    position: absolute;
    inset: 0;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    pointer-events: none;
}
.dwc-donut-center-val {
    font-size: clamp(16px, 1.8vw, 28px);
    font-weight: 800;
    color: #222;
    line-height: 1;
}
.dwc-donut-center-lbl {
    font-size: clamp(8px, .6vw, 10px);
    color: #888;
    letter-spacing: .5px;
    text-transform: uppercase;
    margin-top: 3px;
}

.dwc-legend {
    display: flex;
    flex-direction: column;
    gap: clamp(6px, .7vw, 11px);
    flex-shrink: 0;
    width: clamp(120px, 12vw, 175px);
    min-width: 0;
}
.dwc-legend-item { display: flex; align-items: center; gap: 7px; min-width: 0; }
.dwc-legend-dot {
    width: clamp(8px, .65vw, 10px);
    height: clamp(8px, .65vw, 10px);
    border-radius: 3px;
    flex-shrink: 0;
}
.dwc-legend-name {
    font-size: clamp(9px, .68vw, 11px);
    font-weight: 600;
    color: #333;
    flex: 1;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    min-width: 0;
}
.dwc-legend-pct {
    font-size: clamp(9px, .68vw, 11px);
    font-weight: 800;
    color: #222;
    flex-shrink: 0;
    min-width: 28px;
    text-align: right;
}

.dwc-seg { transition: opacity .2s; cursor: pointer; }
.dwc-seg:hover { opacity: .72; }

/* empty state */
.dwc-empty {
    font-size: 13px;
    color: #aaa;
    text-align: center;
    padding: 20px 0;
}

/* ── BAR CHART ── */
.dwc-bar-chart {
    display: flex;
    align-items: flex-end;
    gap: clamp(3px, .4vw, 7px);
    height: clamp(100px, 10.5vw, 170px);
    width: 100%;
    min-width: 0;
    padding-top: 20px;
    box-sizing: border-box;
}
.dwc-bar-col {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: flex-end;
    gap: 4px;
    height: 100%;
    min-width: 0;
}
.dwc-bar {
    width: 100%;
    border-radius: 5px 5px 0 0;
    background: linear-gradient(180deg, #1a90d9 0%, #00695c 100%);
    position: relative;
    transition: opacity .2s;
    cursor: pointer;
}
.dwc-bar:hover { opacity: .75; }
.dwc-bar-val {
    position: absolute;
    top: -17px;
    left: 50%;
    transform: translateX(-50%);
    font-size: clamp(7px, .58vw, 9px);
    font-weight: 700;
    color: #333;
    white-space: nowrap;
}
.dwc-bar-lbl {
    font-size: clamp(7px, .58vw, 9px);
    color: black;
    text-align: center;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    width: 100%;
}

@media (max-width: 640px) {
    .dwc-grid { grid-template-columns: 1fr; }
    .dwc-donut-wrap { flex-wrap: wrap; }
    .dwc-legend { width: 100%; }
}
.dwc-page {
    width: 100%;
    margin-top: 15px;
    padding: clamp(10px, 1.5vw, 20px)
             clamp(12px, 1.8vw, 24px);
    box-sizing: border-box;
}

.dwc-row-shell {
    width: 100%;
}

.dwc-grid {
    display: grid;
    grid-template-columns: minmax(0,1fr) minmax(0,1fr);
    gap: clamp(10px,1.2vw,18px);
}
</style>
<div class="dwc-page">
<!-- ── ROW 2: Chart cards ── -->
<div class="dwc-row-shell dwc-row-gap">
    <div class="dwc-grid">

        <!-- LEFT: DONUT — Grant Breakdown -->
        <div class="dwc-card">
            <div class="dwc-title">Grant Breakdown</div>

            <?php if (empty($dw_grants)): ?>
                <div class="dwc-empty">No grant data available.</div>
            <?php else: ?>

            <div class="dwc-donut-wrap">
                <div class="dwc-donut-svg-wrap">
                    <?php
                    $cx = 105; $cy = 105; $r = 86; $stroke = 28;
                    $inner_r   = $r - $stroke / 2;
                    $circumf   = 2 * M_PI * $inner_r;
                    $offset    = 0;
                    $gap_deg   = 2;
                    $effective = 360 - count($dw_grants) * $gap_deg;
                    ?>
                    <svg viewBox="0 0 210 210" xmlns="http://www.w3.org/2000/svg"
                         style="transform:rotate(-90deg);">
                        <circle cx="<?= $cx ?>" cy="<?= $cy ?>" r="<?= $inner_r ?>"
                                fill="none" stroke="#f0f0f0" stroke-width="<?= $stroke ?>"/>
                        <?php foreach ($dw_grants as $g):
                            $pct      = $dw_grant_total > 0 ? $g['value'] / $dw_grant_total : 0;
                            $seg_deg  = $pct * $effective;
                            $dash_len = ($seg_deg / 360) * $circumf;
                            $gap_len  = $circumf - $dash_len;
                        ?>
                        <circle class="dwc-seg"
                                cx="<?= $cx ?>" cy="<?= $cy ?>" r="<?= $inner_r ?>"
                                fill="none"
                                stroke="<?= htmlspecialchars($g['color']) ?>"
                                stroke-width="<?= $stroke ?>"
                                stroke-dasharray="<?= round($dash_len,2) ?> <?= round($gap_len,2) ?>"
                                transform="rotate(<?= round($offset,2) ?>, <?= $cx ?>, <?= $cy ?>)"
                                data-label="<?= htmlspecialchars($g['label']) ?>"
                                data-value="<?= $g['value'] ?>">
                            <title><?= htmlspecialchars($g['label']) ?>: <?= $g['value'] ?></title>
                        </circle>
                        <?php $offset += $seg_deg + $gap_deg; endforeach; ?>
                    </svg>
                    <div class="dwc-donut-center">
                        <span class="dwc-donut-center-val"><?= number_format($dw_grant_total) ?></span>
                        <span class="dwc-donut-center-lbl">Total Items</span>
                    </div>
                </div>

                <div class="dwc-legend">
                    <?php foreach ($dw_grants as $g):
                        $pct = $dw_grant_total > 0 ? round($g['value'] / $dw_grant_total * 100) : 0;
                    ?>
                    <div class="dwc-legend-item">
                        <span class="dwc-legend-dot" style="background:<?= htmlspecialchars($g['color']) ?>"></span>
                        <span class="dwc-legend-name" title="<?= htmlspecialchars($g['label']) ?>"><?= htmlspecialchars($g['label']) ?></span>
                        <span class="dwc-legend-pct"><?= $pct ?>%</span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <?php endif; ?>
        </div>

        <!-- RIGHT: BAR — Monthly Equipment Issues -->
        <div class="dwc-card">
            <div class="dwc-title dwc-bar-title">Monthly Equipment Added</div>

            <div class="dwc-bar-chart">
                <?php foreach ($dw_bar_data as $mon => $val):
                    $height_pct = $dw_bar_max > 0 ? ($val / $dw_bar_max) * 100 : 0;
                ?>
                <div class="dwc-bar-col">
                    <div class="dwc-bar"
                         style="height:<?= round($height_pct) ?>%; min-height:4px;"
                         title="<?= $mon ?>: <?= $val ?> items">
                        <span class="dwc-bar-val"><?= $val ?></span>
                    </div>
                    <div class="dwc-bar-lbl"><?= $mon ?></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

    </div>
</div><!-- /.dw-row-shell -->

</div>