<?php

function createReferralCode($DB, $table = "products", $field = "aff_code")
{
    $DB->table = $table;
    $DB->field = $field;

    do {
        $referralCode = generateReferralCode();
        $DB->value = $referralCode;
    } while ($DB->validateField());

    return $referralCode;
}

function generateReferralCode()
{
    $bytes = random_bytes(8);
    $encoded = base64_encode($bytes);
    $stripped = str_replace(['=', '+', '/'], '', $encoded);

    return $stripped;
}
?>