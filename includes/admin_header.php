<?php
/**
 * SOPHEA - Admin Panel Header
 * 
 * Header component for admin panel with search and notifications
 */

// Make sure auth is loaded
if (!class_exists('Auth')) {
    require_once __DIR__ . '/../classes/Auth.php';
}

// Only check auth if $currentUser is not already set
if (!isset($currentUser)) {
    $auth = new Auth();
    if (!$auth->isLoggedIn()) {
        header('Location: admin.php');
        exit;
    }
    $currentUser = $auth->getCurrentUser();
}

// Fallback if still not set
if (!isset($currentUser) || !$currentUser) {
    $currentUser = ['username' => 'Admin', 'full_name' => 'Administrador'];
}
?>
<!DOCTYPE html>
<html class="dark" lang="es">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title><?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) : 'Panel de Gestión - Sophea'; ?></title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" sizes="192x192" href="assets/ico_sp192x192.png">
    <link rel="apple-touch-icon" sizes="192x192" href="assets/ico_sp192x192.png">
    <link rel="shortcut icon" type="image/x-icon" href="assets/favicon.ico">
    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com" rel="preconnect"/>
    <link crossorigin="" href="https://fonts.gstatic.com" rel="preconnect"/>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@200..800&display=swap" rel="stylesheet"/>
    
    <!-- Material Symbols -->
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    
    <!-- Compiled Tailwind CSS -->
    <link href="assets/css/style.css" rel="stylesheet">
    
    <style>
        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
        }
        .icon-filled {
            font-variation-settings: 'FILL' 1, 'wght' 400, 'GRAD' 0, 'opsz' 24;
        }
        /* Custom scrollbar for tables */
        .custom-scrollbar::-webkit-scrollbar {
            height: 6px;
            width: 6px;
        }
        .custom-scrollbar::-webkit-scrollbar-track {
            background: transparent;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb {
            background-color: #374151;
            border-radius: 20px;
        }
    </style>
</head>
<body class="bg-background-light dark:bg-background-dark font-display text-slate-900 dark:text-slate-50 transition-colors duration-200">

