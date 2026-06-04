<?php
/* ===============================================================
   cost-chart.php
   Cost Breakdown Donut + Monthly Cost Bar Chart
   =============================================================== */

/* ── Cost Donut: Cost by Grant Type ── */
$dwcost_colors = [
    '#1565c0', '#00695c', '#6a1b9a', '#e65100',
    '#b71c1c', '#2e7d32', '#f9a825', '#4527a0',
    '#00838f', '#ad1457'
];

$dwcost_grants = [];
$dwcost_total  = 0;

$q = $connect->query("
    SELECT
        gm.grant_type,
        SUM(em.cost * em.qty_received) AS total_cost
    FROM equipment_master em
    INNER JOIN grants_master gm
        ON gm.id = em.grant_id
    GROUP BY gm.grant_type
    ORDER BY gm.grant_type
");

if ($q) {
    $i = 0;

    while ($row = $q->fetch_assoc()) {

        $cost = (float)$row['total_cost'];

        $dwcost_grants[] = [
            'label' => strtoupper($row['grant_type']),
            'value' => $cost,
            'color' => $dwcost_colors[$i % count($dwcost_colors)]
        ];

        $dwcost_total += $cost;
        $i++;
    }
}

/* ── Monthly Cost (12 Months) ── */

$dwcost_months = [];

for ($i = 11; $i >= 0; $i--) {
    $dwcost_months[date('M', strtotime("-{$i} months"))] = 0;
}

$q2 = $connect->query("
    SELECT
        DATE_FORMAT(created_at,'%b') AS month_name,
        DATE_FORMAT(created_at,'%Y-%m') AS month_sort,
        SUM(cost * qty_received) AS total_cost
    FROM equipment_master
    WHERE created_at >= DATE_FORMAT(
            DATE_SUB(CURDATE(), INTERVAL 11 MONTH),
            '%Y-%m-01'
          )
    GROUP BY month_sort, month_name
    ORDER BY month_sort
");

if ($q2) {
    while ($row = $q2->fetch_assoc()) {
        $dwcost_months[$row['month_name']] = (float)$row['total_cost'];
    }
}

$dwcost_max = max($dwcost_months ?: [1]);
?>

<style>

.dwcost-page{
    width:100%;
    margin-top:15px;
    padding:clamp(10px,1.5vw,20px)
            clamp(12px,1.8vw,24px);
    box-sizing:border-box;
}

.dwcost-grid{
    display:grid;
    grid-template-columns:repeat(2,minmax(0,1fr));
    gap:18px;
}

.dwcost-card{
    background:#fff;
    border-radius:16px;
    padding:20px;
    box-shadow:0 6px 18px rgba(0,0,0,.10);
}

.dwcost-title{
    font-size:13px;
    font-weight:800;
    text-transform:uppercase;
    border-left:4px solid #1565c0;
    padding-left:10px;
    margin-bottom:20px;
}

.dwcost-donut-wrap{
    display:flex;
    align-items:center;
    justify-content:center;
    gap:20px;
}

.dwcost-svg{
    width:200px;
    height:200px;
    position:relative;
}

.dwcost-center{
    position:absolute;
    inset:0;
    display:flex;
    flex-direction:column;
    align-items:center;
    justify-content:center;
}

.dwcost-total{
    font-size:24px;
    font-weight:800;
}

.dwcost-label{
    font-size:11px;
    color:#777;
}

.dwcost-legend{
    display:flex;
    flex-direction:column;
    gap:8px;
}

.dwcost-item{
    display:flex;
    align-items:center;
    gap:8px;
}

.dwcost-dot{
    width:10px;
    height:10px;
    border-radius:3px;
}

.dwcost-name{
    flex:1;
    font-size:11px;
    font-weight:600;
}

.dwcost-pct{
    font-size:11px;
    font-weight:800;
}

.dwcost-bar-chart{
    display:flex;
    align-items:flex-end;
    gap:6px;
    height:180px;
}

.dwcost-col{
    flex:1;
    display:flex;
    flex-direction:column;
    justify-content:flex-end;
    align-items:center;
    height:100%;
}

.dwcost-bar{
    width:100%;
    background:linear-gradient(180deg,#ff9800,#e65100);
    border-radius:6px 6px 0 0;
    position:relative;
}

.dwcost-value{
    position:absolute;
    top:-18px;
    left:50%;
    transform:translateX(-50%);
    font-size:9px;
    font-weight:700;
}

.dwcost-month{
    font-size:9px;
    margin-top:5px;
}
.dwcost-page{
    opacity:0;
    transform:translateY(60px);
    transition:
        opacity .8s ease,
        transform .8s ease;
}

.dwcost-page.show{
    opacity:1;
    transform:translateY(0);
}
@media(max-width:768px){

    .dwcost-grid{
        grid-template-columns:1fr;
    }

    .dwcost-donut-wrap{
        flex-direction:column;
    }
}
</style>

<div class="dwcost-page">

    <div class="dwcost-grid">

        <!-- COST DONUT -->
        <div class="dwcost-card">

            <div class="dwcost-title">
                Cost By Grant Type
            </div>

            <div class="dwcost-donut-wrap">

                <div class="dwcost-svg">

                    <?php
                    $cx=105;
                    $cy=105;
                    $r=86;
                    $stroke=28;

                    $inner=$r-$stroke/2;
                    $circ=2*pi()*$inner;

                    $offset=0;
                    $gap=2;
                    $effective=360-(count($dwcost_grants)*$gap);
                    ?>

                    <svg viewBox="0 0 210 210"
                         style="width:100%;height:100%;transform:rotate(-90deg);">

                        <circle
                            cx="<?=$cx?>"
                            cy="<?=$cy?>"
                            r="<?=$inner?>"
                            fill="none"
                            stroke="#eee"
                            stroke-width="<?=$stroke?>"/>

                        <?php foreach($dwcost_grants as $g):

                            $pct = $dwcost_total > 0
                                ? $g['value'] / $dwcost_total
                                : 0;

                            $deg = $pct * $effective;

                            $dash = ($deg/360)*$circ;
                            $gapLen = $circ-$dash;
                        ?>

                        <circle
                            cx="<?=$cx?>"
                            cy="<?=$cy?>"
                            r="<?=$inner?>"
                            fill="none"
                            stroke="<?=$g['color']?>"
                            stroke-width="<?=$stroke?>"
                            stroke-dasharray="<?=round($dash,2)?> <?=round($gapLen,2)?>"
                            transform="rotate(<?=$offset?>,<?=$cx?>,<?=$cy?>)">
                        </circle>

                        <?php
                        $offset += $deg + $gap;
                        endforeach;
                        ?>

                    </svg>

                    <div class="dwcost-center">
                        <div class="dwcost-total">
                            ₹<?= number_format($dwcost_total,0) ?>
                        </div>
                        <div class="dwcost-label">
                            Total Cost
                        </div>
                    </div>

                </div>

                <div class="dwcost-legend">

                    <?php foreach($dwcost_grants as $g):

                        $pct = $dwcost_total > 0
                            ? round(($g['value']/$dwcost_total)*100)
                            : 0;
                    ?>

                    <div class="dwcost-item">

                        <span
                            class="dwcost-dot"
                            style="background:<?=$g['color']?>;">
                        </span>

                        <span class="dwcost-name">
                            <?=$g['label']?>
                        </span>

                        <span class="dwcost-pct">
                            <?=$pct?>%
                        </span>

                    </div>

                    <?php endforeach; ?>

                </div>

            </div>

        </div>

        <!-- MONTHLY COST BAR -->
        <div class="dwcost-card">

            <div class="dwcost-title">
                Monthly Cost Added
            </div>

            <div class="dwcost-bar-chart">

                <?php foreach($dwcost_months as $month=>$cost):

                    $h = $dwcost_max > 0
                        ? ($cost / $dwcost_max) * 100
                        : 0;
                ?>

                <div class="dwcost-col">

                    <div
                        class="dwcost-bar"
                        style="height:<?=$h?>%;min-height:4px;">

                        <span class="dwcost-value">
                            <?= number_format($cost/1000,0) ?>K
                        </span>

                    </div>

                    <div class="dwcost-month">
                        <?=$month?>
                    </div>

                </div>

                <?php endforeach; ?>

            </div>

        </div>

    </div>

</div>
<script>
    const observer = new IntersectionObserver(entries => {

entries.forEach(entry => {

    if(entry.isIntersecting){
        entry.target.classList.add('show');
    }

});

},{
threshold:0.15
});

document.querySelectorAll('.dwcost-page')
.forEach(el => observer.observe(el));
</script>