<?php
// php-version/settings.php
require_once '../includes/functions.php';

if (!isLoggedIn()) {
    redirect('../login.php');
}

$user = getAuthUser();
$db = Database::connect();

$success_msg = '';
$error_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'update_profile') {
        $business_name = sanitize($_POST['business_name']);
        $email = sanitize($_POST['email']);
        
        try {
            $stmt = $db->prepare("UPDATE users SET business_name = ?, email = ? WHERE id = ?");
            $stmt->execute([$business_name, $email, $user['id']]);
            $success_msg = "Profile updated successfully!";
            $user = getAuthUser();
        } catch (Exception $e) {
            $error_msg = "Failed to update profile: " . $e->getMessage();
        }
    } elseif ($_POST['action'] === 'update_settlement') {
        $bank_info = explode('|', $_POST['bank_data']);
        $bank_name = sanitize($bank_info[0]);
        $bank_code = sanitize($bank_info[1] ?? '');
        $account_number = sanitize($_POST['account_number']);
        $payout_method = sanitize($_POST['payout_method']);
        $currency = sanitize($_POST['settlement_currency']);
        
        try {
            $stmt = $db->prepare("UPDATE users SET settlement_bank = ?, settlement_bank_code = ?, settlement_account_number = ?, payout_method = ?, settlement_currency = ? WHERE id = ?");
            $stmt->execute([$bank_name, $bank_code, $account_number, $payout_method, $currency, $user['id']]);
            $success_msg = "Settlement details updated successfully!";
            $user = getAuthUser();
        } catch (Exception $e) {
            $error_msg = "Failed to update settlement details: " . $e->getMessage();
        }
    }
}

// Fetch bank list from Paystack
$banks_response = paystack_call('bank?currency=NGN');
$banks = $banks_response['data'] ?? [];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - Payhub</title>
<script>
        document.addEventListener('DOMContentLoaded', () => {
            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }
        });
    </script>
</body>
</html>