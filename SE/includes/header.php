<?php
/**
 * Expects (optionally set before include):
 *   $pageTitle        - browser tab title
 *   $breadcrumbParent - e.g. "School Event"
 *   $breadcrumbCurrent- e.g. "Event Planning & Creation"
 *   $activeModule     - sidebar highlight key
 */
$pageTitle = $pageTitle ?? 'Smart School Event Management System';
$user = currentUser();
$initial = $user ? strtoupper(substr($user['full_name'], 0, 1)) : '?';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
<div class="app-shell">
    <?php include __DIR__ . '/sidebar.php'; ?>

    <div class="main">
        <div class="topbar">
            <div class="topbar-left">
                <button type="button" class="sidebar-toggle" id="sidebarToggle" aria-label="Toggle navigation">☰</button>
                <div class="breadcrumb">
                    <span class="crumb-label">School Event Management</span>
                    <?= htmlspecialchars($breadcrumbParent ?? 'School Event') ?>
                    <?php if (!empty($breadcrumbCurrent)): ?>
                        &nbsp;&rsaquo;&nbsp; <strong><?= htmlspecialchars($breadcrumbCurrent) ?></strong>
                    <?php endif; ?>
                </div>
            </div>
            <div class="topbar-right">
                <span class="topbar-date"><?= date('g:i A') ?> &middot; <?= date('D, M j, Y') ?></span>
                <?php if ($user): ?>
                    <div class="user-chip">
                        <div class="user-avatar"><?= htmlspecialchars($initial) ?></div>
                        <span><?= htmlspecialchars($user['full_name']) ?> (<?= htmlspecialchars(ucfirst($user['role'])) ?>)</span>
                    </div>
                    <a href="/logout.php" class="logout-link">Logout</a>
                <?php endif; ?>
            </div>
        </div>
        <div class="content">
