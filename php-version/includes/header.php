<?php
// php-version/header.php
require_once __DIR__ . '/functions.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? 'Payhub - Modern Payments'; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-white text-slate-900">
    <nav class="fixed top-0 w-full bg-white/80 backdrop-blur-md z-50 border-b border-slate-100">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-20">
                <a href="<?php echo BASE_URL; ?>index.php" class="flex items-center gap-2">
                    <?php $logo = getConfig('site_logo'); ?>
                    <?php if ($logo): ?>
                        <img src="<?php echo BASE_URL; ?>uploads/<?php echo $logo; ?>" alt="Logo" class="h-10 object-contain">
                    <?php else: ?>
                        <div class="w-10 h-10 bg-indigo-600 rounded-xl flex items-center justify-center">
                            <i data-lucide="credit-card" class="text-white w-6 h-6"></i>
                        </div>
                    <?php endif; ?>
                    <span class="text-2xl font-bold tracking-tight text-slate-900"><?php echo getConfig('site_name', 'Payhub'); ?></span>
                </a>
                
                <div class="hidden md:flex items-center gap-8">
                    <a href="<?php echo BASE_URL; ?>pricing.php" class="text-sm font-medium text-slate-600 hover:text-indigo-600 transition-colors">Pricing</a>
                    <a href="<?php echo BASE_URL; ?>docs.php" class="text-sm font-medium text-slate-600 hover:text-indigo-600 transition-colors">Developers</a>
                    <a href="<?php echo BASE_URL; ?>blog.php" class="text-sm font-medium text-slate-600 hover:text-indigo-600 transition-colors">Blog</a>
                    <?php if (isLoggedIn()): ?>
                        <a href="<?php echo BASE_URL; ?>merchant/dashboard.php" class="text-sm font-medium text-slate-600 hover:text-indigo-600 transition-colors">Dashboard</a>
                        <a href="<?php echo BASE_URL; ?>logout.php" class="bg-indigo-600 text-white px-6 py-2.5 rounded-full text-sm font-semibold hover:bg-indigo-700 transition-all shadow-lg shadow-indigo-200">Logout</a>
                    <?php else: ?>
                        <a href="<?php echo BASE_URL; ?>login.php" class="text-sm font-medium text-slate-600 hover:text-indigo-600 transition-colors">Login</a>
                        <a href="<?php echo BASE_URL; ?>register.php" class="bg-indigo-600 text-white px-6 py-2.5 rounded-full text-sm font-semibold hover:bg-indigo-700 transition-all shadow-lg shadow-indigo-200">Create free account</a>
                    <?php endif; ?>
                </div>

                <button class="md:hidden text-slate-600">
                    <i data-lucide="menu"></i>
                </button>
            </div>
        </div>
    </nav>
