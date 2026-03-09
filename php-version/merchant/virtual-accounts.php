<?php
// php-version/virtual-accounts.php
require_once '../includes/functions.php';

if (!isLoggedIn()) {
    redirect('../login.php');
}

$user = getAuthUser();
if ($user['business_type'] === 'Starter' || $user['is_kyc_verified'] != 1) {
    redirect('dashboard.php');
}
$db = Database::connect();

// Fetch virtual accounts
$stmt = $db->prepare("SELECT * FROM virtual_accounts WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$user['id']]);
$accounts = $stmt->fetchAll();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Virtual Accounts - Payhub</title>
<script>
        document.addEventListener('DOMContentLoaded', () => {
            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }
        });
    </script>
</body>
</html>