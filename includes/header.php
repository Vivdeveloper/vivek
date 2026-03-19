<?php
/**
 * Public Website Header — SEO Website Designer
 */
startSecureSession();
$_currentUser = currentUser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?= APP_NAME ?> - Expert SEO, Web Design & Digital Marketing Agency. We build websites that rank and convert.">
    <meta name="keywords" content="SEO, website design, digital marketing, web development, SEO agency, website designer">
    <title><?php 
        $cleanTitle = strip_tags($pageTitle ?? 'Home'); 
        echo h($cleanTitle); 
    ?> | <?= APP_NAME ?></title>
    
    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?= APP_URL ?>">
    <meta property="og:title" content="<?= h($cleanTitle) ?> | <?= APP_NAME ?>">
    <meta property="og:description" content="<?= h(getSetting('footer_desc')) ?>">
    <?php if (!empty($page['featured_image'])): ?>
    <meta property="og:image" content="<?= APP_URL . '/' . h($page['featured_image']) ?>">
    <?php endif; ?>

    <!-- Twitter -->
    <meta property="twitter:card" content="summary_large_image">
    <meta property="twitter:url" content="<?= APP_URL ?>">
    <meta property="twitter:title" content="<?= h($cleanTitle) ?> | <?= APP_NAME ?>">
    <meta property="twitter:description" content="<?= h(getSetting('footer_desc')) ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/style.css">
    
    <!-- Global CSS Overrides from Admin -->
    <?php $globalCss = getSetting('custom_css'); if($globalCss): ?>
    <style><?= $globalCss ?></style>
    <?php endif; ?>

    <!-- Header Scripts from Admin (Global & Page Specific) -->
    <?php $headerCode = getSetting('custom_code_header'); if($headerCode) echo $headerCode; ?>
    <?php $headerScripts = getSetting('header_scripts'); if($headerScripts) echo $headerScripts; ?>
    <?php if (isset($page['custom_css']) && !empty($page['custom_css'])): ?>
    <style><?= $page['custom_css'] ?></style>
    <?php endif; ?>
</head>
<body>
    <!-- Body Start Scripts from Admin -->
    <?php $bodyCode = getSetting('custom_code_body'); if($bodyCode) echo $bodyCode; ?>

    <!-- Flash Messages -->
    <?php displayFlash(); ?>

    <?php if (($page['template'] ?? 'default') !== 'canvas'): ?>
        <?php renderHeader(); ?>
    <?php endif; ?>

    <main class="main-content" style="<?= ($page['template'] ?? 'default') === 'canvas' ? 'padding:0; margin:0;' : '' ?>">
