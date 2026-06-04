<?php
function audit_log(
    $connect,
    $user_id,
    $username,
    $action,
    $module,
    $reference_id,
    $description
){
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';

    $stmt = $connect->prepare("
        INSERT INTO audit_logs
        (user_id, username, action, module, reference_id, description, ip_address)
        VALUES (?,?,?,?,?,?,?)
    ");
    $stmt->bind_param(
        "isssiss",
        $user_id,
        $username,
        $action,
        $module,
        $reference_id,
        $description,
        $ip
    );
    $stmt->execute();
    $stmt->close();
}
