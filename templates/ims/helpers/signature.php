function generate_signature($user, $doc_no){
    $dateTime = date('d-M-Y H:i:s');

    $rawText = "
Electronically Signed By {$user['rank']} {$user['username']}
({$user['unit']})

On {$dateTime}
(Login ID : {$user['login_id']})

Authentication : Office Login (System Generated)
Document Ref : {$doc_no}
";

    // 🔐 HASH (Tamper Proof)
    $hash = hash('sha256', $rawText.$user['id']);

    $finalText = $rawText."Signature Hash : {$hash}";

    return [
        'text' => $finalText,
        'hash' => $hash,
        'time' => date('Y-m-d H:i:s')
    ];
}
