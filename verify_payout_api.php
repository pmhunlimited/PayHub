<?php
// verify_payout_api.php
require_once 'php-version/includes/functions.php';

echo "1. Checking Payout API Syntax...\n";
$output = shell_exec('php -l php-version/api/payout/initialize.php');
echo $output;

echo "\n2. Checking .htaccess Routing...\n";
$htaccess = file_get_contents('php-version/api/payout/.htaccess');
if (strpos($htaccess, 'RewriteRule ^initialize$ initialize.php') !== false) {
    echo "SUCCESS: .htaccess routing correctly configured.\n";
} else {
    echo "FAILURE: .htaccess routing misconfigured.\n";
}

echo "\n3. Checking Documentation...\n";
$docs = file_get_contents('php-version/api-reference.php');
if (strpos($docs, 'id="payout-initialize"') !== false && strpos($docs, 'api/payout/initialize') !== false) {
    echo "SUCCESS: Documentation includes Payout API.\n";
} else {
    echo "FAILURE: Documentation missing Payout API.\n";
}

echo "\n4. Functional Check (Simulation)...\n";
// We can't easily run a full curl request without a live server and DB setup here,
// but we can check if the file exists and has the required logical components.
$content = file_get_contents('php-version/api/payout/initialize.php');
$checks = [
    'Bearer token' => 'str_replace(\'Bearer \', \'\', $auth)',
    '24h Limit' => 'DATE_SUB(NOW(), INTERVAL 24 HOUR)',
    'Paystack Call' => 'paystack_payout',
    'JSON Response' => 'json_encode'
];

foreach ($checks as $name => $snippet) {
    if (strpos($content, $snippet) !== false) {
        echo "SUCCESS: Logic for '$name' found in API file.\n";
    } else {
        echo "FAILURE: Logic for '$name' NOT found in API file.\n";
    }
}
