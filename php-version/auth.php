<?php
// php-version/auth.php
require_once 'db.php';
require_once 'functions.php';

$db = Database::connect();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'register') {
        $email = sanitize($_POST['email']);
        $password = $_POST['password'];
        $full_name = sanitize($_POST['full_name']);
        $business_name = sanitize($_POST['business_name']);

        if (empty($email) || empty($password)) {
            $error = "Email and password are required.";
        } else {
            $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $error = "Email already registered.";
            } else {
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                $public_key = generateApiKey('pk_live_');
                $secret_key = generateApiKey('sk_live_');
                
                $stmt = $db->prepare("INSERT INTO users (email, password_hash, full_name, business_name, public_key, secret_key) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$email, $password_hash, $full_name, $business_name, $public_key, $secret_key]);
                
                $_SESSION['user_id'] = $db->lastInsertId();
                $_SESSION['role'] = 'merchant';
                redirect('dashboard.php');
            }
        }
    } elseif ($action === 'login') {
        $email = sanitize($_POST['email']);
        $password = $_POST['password'];

        $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];
            if ($user['role'] === 'admin') {
                redirect('admin.php');
            } else {
                redirect('dashboard.php');
            }
        } else {
            $error = "Invalid email or password.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Auth - Payhub</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 flex items-center justify-center min-h-screen">
    <div class="max-w-md w-full p-8 bg-white rounded-2xl shadow-sm border border-gray-100">
        <h1 class="text-2xl font-bold text-gray-900 mb-6 text-center">Payhub</h1>
        
        <?php if ($error): ?>
            <div class="bg-red-50 text-red-600 p-3 rounded-lg mb-4 text-sm"><?php echo $error; ?></div>
        <?php endif; ?>

        <div id="login-form" class="<?php echo isset($_GET['register']) ? 'hidden' : ''; ?>">
            <form method="POST">
                <input type="hidden" name="action" value="login">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                        <input type="email" name="email" required class="w-full p-3 border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                        <input type="password" name="password" required class="w-full p-3 border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none">
                    </div>
                    <button type="submit" class="w-full bg-indigo-600 text-white p-3 rounded-lg font-medium hover:bg-indigo-700 transition">Login</button>
                </div>
            </form>
            <p class="mt-4 text-center text-sm text-gray-600">Don't have an account? <a href="?register=1" class="text-indigo-600 font-medium">Register</a></p>
        </div>

        <div id="register-form" class="<?php echo !isset($_GET['register']) ? 'hidden' : ''; ?>">
            <form method="POST">
                <input type="hidden" name="action" value="register">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Full Name</label>
                        <input type="text" name="full_name" required class="w-full p-3 border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Business Name</label>
                        <input type="text" name="business_name" required class="w-full p-3 border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                        <input type="email" name="email" required class="w-full p-3 border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                        <input type="password" name="password" required class="w-full p-3 border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none">
                    </div>
                    <button type="submit" class="w-full bg-indigo-600 text-white p-3 rounded-lg font-medium hover:bg-indigo-700 transition">Create Account</button>
                </div>
            </form>
            <p class="mt-4 text-center text-sm text-gray-600">Already have an account? <a href="?" class="text-indigo-600 font-medium">Login</a></p>
        </div>
    </div>
</body>
</html>
