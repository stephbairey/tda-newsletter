<?php
/** Shared <head> + top bar for the admin pages. Expects $adminTitle. */
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title><?= e($adminTitle) ?> — TDA Currents</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,700;0,800;1,700&family=Archivo:ital,wght@0,400;0,500;0,600;0,700;1,400&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= asset('css/editor.css') ?>">
</head>
<body class="admin">
<div class="admin-bar">
  <a class="admin-brand" href="?page=dashboard">
    <img src="assets/anchor-logo.svg" alt="">
    <span>TDA <em>Currents</em></span>
    <span class="admin-brand-sub">Editor</span>
  </a>
  <nav class="admin-nav">
    <a href="?page=dashboard">Issues</a>
    <a href="?page=settings">Settings</a>
    <a href="?" target="_blank">View newsletter</a>
  </nav>
</div>
