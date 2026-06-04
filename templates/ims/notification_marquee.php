<?php
include 'connect.php';

$amcTables = [
    'gofnms' => 'name',
    'gofnms1' => 'name',
    'dwdm' => 'name',
    'ipmpls' => 'name',
    'hcmceu' => 'name',
    'lcmceu' => 'name',
    'microwave' => 'name',
    'portablesatl' => 'name',
    'satl' => 'name'
];

$unionQueries = [];

foreach ($amcTables as $table => $eqColumn) {
    $unionQueries[] = "
        SELECT 
            '$table' AS table_name, 
            id, 
            $eqColumn AS equipment_name, 
            amc
        FROM $table
        WHERE DATEDIFF(amc, CURDATE()) <= 15 
        AND DATEDIFF(amc, CURDATE()) >= 0
    ";
}

$finalQuery = implode(" UNION ALL ", $unionQueries);
$result = $connect->query($finalQuery);

$notifications = [];

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {

        $notifications[] = "🔔 <b>" . strtoupper($row['table_name']) . "</b> — 
                            <strong>" . htmlspecialchars($row['equipment_name']) . "</strong> 
                            AMC Due on <b>" . date('d M Y', strtotime($row['amc'])) . "</b>";
    }
}

echo '<marquee behavior="scroll" direction="left" scrollamount="6" class="amc-marquee">';

if (!empty($notifications)) {
    echo implode(' &nbsp;&nbsp; | &nbsp;&nbsp; ', $notifications);
} else {
    echo '✅ No AMC Due soon.';
}

echo '</marquee>';
?>
