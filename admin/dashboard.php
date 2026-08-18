<?php
include("../functions.php");
$accessToken = getAccessTokenFromSession();
$isAdmin = false;

if ($accessToken) {
    $role = getUserRoleFromAccessToken($accessToken);
    $isAdmin = ($role === 'admin');
}

if (!$isAdmin) {
    header("Location: ../index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Sales Dashboard — The Salty</title>
  <link rel="manifest" href="manifest.json">
  <!-- Mobile web app meta tags for Add to Home Screen -->
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-status-bar-style" content="default">
  <meta name="apple-mobile-web-app-title" content="Salty Admin">
  <link rel="apple-touch-icon" href="../assets/img/logo.svg">

  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet" />
  <!-- Chart.js CDN -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

  <!-- Service Worker Registration -->
  <script>
    if ('serviceWorker' in navigator) {
      window.addEventListener('load', () => {
        navigator.serviceWorker.register('sw.js')
          .then(reg => console.log('Admin Service Worker registered:', reg.scope))
          .catch(err => console.error('Admin Service Worker registration failed:', err));
      });
    }
  </script>

  <style>
    /* ── CSS Variables — mirror the existing site palette ─────────── */
    :root {
      --first-color: #d32f2f;
      --first-color-alt: #b71c1c;
      --red-color: #d32f2f;
      --title-color: #19191a;
      --text-color: #555;
      --text-color-light: #888;
      --border-color: #e8e8e8;
      --border-color-alt: #d0d0d0;
      --body-color: #f5f5f5;
      --container-color: #fff;
      --font: 'Poppins', sans-serif;
      --small-font: .8rem;
      --normal-font: .95rem;
      --h3-font: 1.1rem;
      --shadow: 0 2px 12px rgba(0,0,0,.07);
      --radius: .5rem;
    }

    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    body {
      font-family: var(--font);
      background: var(--body-color);
      color: var(--text-color);
      font-size: var(--normal-font);
    }

    a { color: inherit; text-decoration: none; }

    /* ── Layout ──────────────────────────────────────────────────── */
    .wrapper { display: flex; min-height: 100vh; }

    /* Sidebar */
    .sidebar {
      width: 220px;
      background: var(--container-color);
      border-right: 1px solid var(--border-color);
      display: flex;
      flex-direction: column;
      position: sticky;
      top: 0;
      height: 100vh;
      flex-shrink: 0;
    }

    .sidebar__brand {
      padding: 1.4rem 1.5rem;
      border-bottom: 1px solid var(--border-color);
      font-weight: 700;
      font-size: 1.1rem;
      color: var(--first-color);
      letter-spacing: .5px;
      display: flex;
      align-items: center;
      gap: .5rem;
    }

    .sidebar__brand span { font-size: 1.3rem; }

    .sidebar__nav { padding: 1rem 0; flex: 1; }

    .nav-item {
      display: flex;
      align-items: center;
      gap: .75rem;
      padding: .65rem 1.5rem;
      cursor: pointer;
      border-left: 3px solid transparent;
      transition: background .15s, border-color .15s;
      font-size: var(--normal-font);
      color: var(--text-color);
    }

    .nav-item:hover { background: #fef2f2; color: var(--first-color); }
    .nav-item.active {
      background: #fef2f2;
      color: var(--first-color);
      border-left-color: var(--first-color);
      font-weight: 500;
    }

    .nav-item .icon { font-size: 1.1rem; width: 20px; text-align: center; }

    /* Main area */
    .main {
      flex: 1;
      display: flex;
      flex-direction: column;
      overflow: hidden;
    }

    /* Top bar */
    .topbar {
      background: var(--container-color);
      border-bottom: 1px solid var(--border-color);
      padding: .9rem 2rem;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 1rem;
    }

    .topbar h1 { font-size: 1.05rem; font-weight: 600; color: var(--title-color); }

    .range-btns { display: flex; gap: .4rem; }

    .range-btn {
      border: 1px solid var(--border-color-alt);
      background: var(--body-color);
      color: var(--text-color);
      border-radius: 2rem;
      padding: .3rem .85rem;
      font-size: var(--small-font);
      cursor: pointer;
      font-family: var(--font);
      transition: background .15s, color .15s;
    }

    .range-btn:hover { background: #fef2f2; color: var(--first-color); border-color: var(--first-color); }
    .range-btn.active { background: var(--first-color); color: #fff; border-color: var(--first-color); }

    /* Content */
    .content {
      padding: 1.5rem 2rem 2rem;
      overflow-y: auto;
      flex: 1;
    }

    /* ── KPI Cards ───────────────────────────────────────────────── */
    .kpi-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
      gap: 1rem;
      margin-bottom: 1.5rem;
    }

    .kpi-card {
      background: var(--container-color);
      border: 1px solid var(--border-color);
      border-radius: var(--radius);
      padding: 1.2rem 1.4rem;
      box-shadow: var(--shadow);
    }

    .kpi-card__label {
      font-size: var(--small-font);
      color: var(--text-color-light);
      margin-bottom: .3rem;
      text-transform: uppercase;
      letter-spacing: .4px;
    }

    .kpi-card__value {
      font-size: 1.55rem;
      font-weight: 700;
      color: var(--title-color);
      line-height: 1.1;
    }

    .kpi-card__sub {
      font-size: .75rem;
      margin-top: .3rem;
    }

    .kpi-card__sub.up { color: #2e7d32; }
    .kpi-card__sub.down { color: var(--first-color); }
    .kpi-card__sub.neutral { color: var(--text-color-light); }

    /* ── Chart row ───────────────────────────────────────────────── */
    .chart-row {
      display: grid;
      grid-template-columns: 2fr 1fr;
      gap: 1rem;
      margin-bottom: 1.5rem;
    }

    .chart-row-3 {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 1rem;
      margin-bottom: 1.5rem;
    }

    /* ── Card shell ──────────────────────────────────────────────── */
    .card {
      background: var(--container-color);
      border: 1px solid var(--border-color);
      border-radius: var(--radius);
      box-shadow: var(--shadow);
      overflow: hidden;
    }

    .card__header {
      padding: 1rem 1.4rem .6rem;
      display: flex;
      align-items: center;
      justify-content: space-between;
      border-bottom: 1px solid var(--border-color);
    }

    .card__title {
      font-size: var(--h3-font);
      font-weight: 600;
      color: var(--title-color);
    }

    .card__body { padding: 1.2rem 1.4rem; }

    .chart-wrap { position: relative; height: 240px; }

    /* ── Tables ──────────────────────────────────────────────────── */
    .data-table { width: 100%; border-collapse: collapse; }

    .data-table th {
      font-size: .75rem;
      text-transform: uppercase;
      color: var(--text-color-light);
      letter-spacing: .5px;
      padding: .5rem .75rem;
      text-align: left;
      border-bottom: 1px solid var(--border-color);
    }

    .data-table td {
      padding: .6rem .75rem;
      font-size: var(--small-font);
      border-bottom: 1px solid var(--border-color);
      vertical-align: middle;
    }

    .data-table tr:last-child td { border-bottom: none; }
    .data-table tr:hover td { background: #fafafa; }
    
    .data-table tr.clickable-row {
      cursor: pointer;
    }
    .data-table tr.clickable-row:hover td {
      background: #fef2f2 !important;
    }
    .data-table tr.clickable-row .order-view-indicator {
      transition: transform 0.15s ease;
      display: inline-block;
    }
    .data-table tr.clickable-row:hover .order-view-indicator {
      transform: scale(1.2);
    }

    /* Status badges */
    .badge {
      display: inline-block;
      padding: .18rem .55rem;
      border-radius: 2rem;
      font-size: .7rem;
      font-weight: 600;
      text-transform: capitalize;
    }

    .badge--pending   { background: #fff3e0; color: #e65100; }
    .badge--completed { background: #e8f5e9; color: #2e7d32; }
    .badge--canceled  { background: #fce4ec; color: #c62828; }
    .badge--paid      { background: #e8f5e9; color: #2e7d32; }
    .badge--unpaid    { background: #fff3e0; color: #e65100; }
    .badge--low       { background: #fce4ec; color: #c62828; }
    .badge--ok        { background: #e8f5e9; color: #2e7d32; }

    /* Bar fill (top products) */
    .bar-fill { background: #fce4ec; border-radius: 2px; height: 6px; }
    .bar-fill__inner { background: var(--first-color); height: 100%; border-radius: 2px; }

    /* ── Loading / error state ───────────────────────────────────── */
    .skeleton {
      background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
      background-size: 200% 100%;
      animation: shimmer 1.2s infinite;
      border-radius: 4px;
      height: 1.2rem;
      margin-bottom: .5rem;
    }

    @keyframes shimmer { 0% { background-position: 200% 0; } 100% { background-position: -200% 0; } }

    .error-msg { color: var(--first-color); font-size: var(--small-font); padding: 1rem; }

    /* ── Responsive ──────────────────────────────────────────────── */
    @media (max-width: 900px) {
      .chart-row   { grid-template-columns: 1fr; }
      .chart-row-3 { grid-template-columns: 1fr 1fr; }
    }

    @media (max-width: 768px) {
      .sidebar     { display: none; }
      .content     { padding: 1rem; }
      .kpi-grid    { grid-template-columns: 1fr 1fr; }
      .chart-row-3 { grid-template-columns: 1fr; }
      .topbar      {
        padding: 1rem;
        flex-direction: column;
        align-items: flex-start;
        gap: .8rem;
      }
      .topbar > div {
        width: 100%;
        justify-content: space-between;
      }
      .status-select {
        padding: .35rem .6rem !important;
        padding-right: 1.6rem !important;
        font-size: .8rem !important;
      }
      .data-table td {
        padding: .8rem .75rem !important;
      }
    }

    @media (max-width: 480px) {
      .modal__grid { grid-template-columns: 1fr !important; gap: .6rem !important; }
      .modal__footer { flex-direction: column !important; gap: .5rem !important; }
      .modal__footer button { width: 100% !important; margin: 0 !important; }
    }

    /* Mini nav for mobile */
    .mobile-nav {
      display: none;
      position: fixed;
      bottom: 0;
      left: 0;
      right: 0;
      background: var(--container-color);
      border-top: 1px solid var(--border-color);
      box-shadow: 0 -2px 10px rgba(0,0,0,0.06);
      padding: .6rem 0 calc(.6rem + env(safe-area-inset-bottom));
      z-index: 1000;
      justify-content: space-around;
    }

    .mobile-nav__item {
      display: flex;
      flex-direction: column;
      align-items: center;
      font-size: .75rem;
      cursor: pointer;
      color: var(--text-color-light);
      gap: 3px;
      flex: 1;
      text-align: center;
      transition: color 0.15s ease;
      -webkit-tap-highlight-color: transparent;
    }

    .mobile-nav__item.active {
      color: var(--first-color);
      font-weight: 600;
    }

    .mobile-nav__item .icon {
      font-size: 1.3rem;
    }

    @media (max-width: 768px) {
      .mobile-nav {
        display: flex;
      }
      .content {
        padding-bottom: 6rem;
      }
    }

    /* Refresh button */
    .btn-refresh {
      border: 1px solid var(--border-color-alt);
      background: transparent;
      color: var(--text-color);
      border-radius: var(--radius);
      padding: .3rem .7rem;
      font-size: var(--small-font);
      cursor: pointer;
      font-family: var(--font);
      transition: background .15s;
      display: flex;
      align-items: center;
      gap: .3rem;
    }

    .btn-refresh:hover { background: var(--body-color); }
    .btn-refresh .spin { display: inline-block; transition: transform .3s; }
    .btn-refresh.loading .spin { animation: spin 1s linear infinite; }
    @keyframes spin { to { transform: rotate(360deg); } }

    /* ── Order filters bar ───────────────────────────────────────── */
    .orders-toolbar {
      display: flex;
      gap: .6rem;
      flex-wrap: wrap;
      align-items: center;
      margin-bottom: 1rem;
    }

    .orders-toolbar input[type="text"] {
      border: 1px solid var(--border-color-alt);
      border-radius: var(--radius);
      padding: .4rem .8rem;
      font-family: var(--font);
      font-size: var(--small-font);
      color: var(--title-color);
      outline: none;
      flex: 1;
      min-width: 160px;
      max-width: 260px;
    }

    .orders-toolbar input[type="text"]:focus { border-color: var(--first-color); }

    .filter-select {
      border: 1px solid var(--border-color-alt);
      border-radius: var(--radius);
      padding: .4rem .7rem;
      font-family: var(--font);
      font-size: var(--small-font);
      color: var(--title-color);
      background: var(--container-color);
      cursor: pointer;
      outline: none;
    }

    .filter-select:focus { border-color: var(--first-color); }

    /* Inline status selects inside the table */
    .status-select {
      border: 1px solid transparent;
      border-radius: 2rem;
      padding: .18rem .5rem;
      font-size: .7rem;
      font-weight: 600;
      font-family: var(--font);
      cursor: pointer;
      appearance: none;
      -webkit-appearance: none;
      background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='6'%3E%3Cpath d='M0 0l5 6 5-6z' fill='%23888'/%3E%3C/svg%3E");
      background-repeat: no-repeat;
      background-position: right .4rem center;
      padding-right: 1.4rem;
      transition: border-color .15s, box-shadow .15s;
    }

    .status-select:focus { outline: none; border-color: var(--first-color); box-shadow: 0 0 0 2px rgba(211,47,47,.15); }

    .status-select.s-pending   { background-color: #fff3e0; color: #e65100; border-color: #ffe0b2; }
    .status-select.s-completed { background-color: #e8f5e9; color: #2e7d32; border-color: #c8e6c9; }
    .status-select.s-canceled  { background-color: #fce4ec; color: #c62828; border-color: #f8bbd0; }

    .payment-toggle {
      display: inline-flex;
      align-items: center;
      gap: .35rem;
      cursor: pointer;
      font-size: .7rem;
      font-weight: 600;
      user-select: none;
    }

    .payment-toggle input[type="checkbox"] { display: none; }

    .toggle-track {
      width: 32px;
      height: 17px;
      border-radius: 10px;
      background: #ccc;
      position: relative;
      transition: background .2s;
      flex-shrink: 0;
    }

    .toggle-track::after {
      content: '';
      position: absolute;
      top: 2px; left: 2px;
      width: 13px; height: 13px;
      border-radius: 50%;
      background: #fff;
      transition: transform .2s;
      box-shadow: 0 1px 3px rgba(0,0,0,.2);
    }

    .payment-toggle.paid   .toggle-track { background: #4caf50; }
    .payment-toggle.unpaid .toggle-track { background: #ccc; }
    .payment-toggle.paid   .toggle-track::after { transform: translateX(15px); }

    .payment-toggle .toggle-label { color: var(--text-color-light); }
    .payment-toggle.paid   .toggle-label { color: #2e7d32; }

    /* Saving spinner on row */
    .row-saving td { opacity: .5; pointer-events: none; }

    /* ── Modal ───────────────────────────────────────────────────── */
    .modal-overlay {
      display: none;
      position: fixed;
      inset: 0;
      background: rgba(0,0,0,.45);
      z-index: 2000;
      align-items: center;
      justify-content: center;
      padding: 1rem;
    }

    .modal-overlay.open { display: flex; }

    .modal {
      background: var(--container-color);
      border-radius: var(--radius);
      box-shadow: 0 8px 40px rgba(0,0,0,.18);
      width: 100%;
      max-width: 520px;
      max-height: 90vh;
      overflow-y: auto;
      animation: modalIn .18s ease;
    }

    @keyframes modalIn { from { transform: translateY(12px); opacity: 0; } to { transform: none; opacity: 1; } }

    .modal__header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 1.1rem 1.4rem .8rem;
      border-bottom: 1px solid var(--border-color);
    }

    .modal__title { font-weight: 600; font-size: var(--h3-font); color: var(--title-color); }

    .modal__close {
      background: none;
      border: none;
      font-size: 1.3rem;
      cursor: pointer;
      color: var(--text-color-light);
      line-height: 1;
      padding: .2rem .4rem;
      border-radius: 4px;
    }

    .modal__close:hover { background: var(--body-color); color: var(--title-color); }

    .modal__body { padding: 1.2rem 1.4rem; }

    .modal__section { margin-bottom: 1.1rem; }

    .modal__label {
      font-size: .72rem;
      text-transform: uppercase;
      letter-spacing: .5px;
      color: var(--text-color-light);
      margin-bottom: .3rem;
    }

    .modal__value { font-size: var(--normal-font); color: var(--title-color); }

    .modal__grid { display: grid; grid-template-columns: 1fr 1fr; gap: .8rem; }

    .modal__footer {
      padding: .8rem 1.4rem 1.1rem;
      display: flex;
      gap: .6rem;
      justify-content: flex-end;
      border-top: 1px solid var(--border-color);
    }

    .btn-primary {
      background: var(--first-color);
      color: #fff;
      border: none;
      border-radius: var(--radius);
      padding: .45rem 1.1rem;
      font-family: var(--font);
      font-size: var(--small-font);
      font-weight: 600;
      cursor: pointer;
      transition: background .15s;
    }

    .btn-primary:hover { background: var(--first-color-alt); }
    .btn-primary:disabled { opacity: .6; cursor: not-allowed; }

    .btn-secondary {
      background: transparent;
      color: var(--text-color);
      border: 1px solid var(--border-color-alt);
      border-radius: var(--radius);
      padding: .45rem 1.1rem;
      font-family: var(--font);
      font-size: var(--small-font);
      cursor: pointer;
      transition: background .15s;
    }

    .btn-secondary:hover { background: var(--body-color); }

    .modal select, .modal .form-select {
      width: 100%;
      border: 1px solid var(--border-color-alt);
      border-radius: var(--radius);
      padding: .5rem .75rem;
      font-family: var(--font);
      font-size: var(--normal-font);
      color: var(--title-color);
      background: var(--container-color);
      outline: none;
      cursor: pointer;
    }

    .modal select:focus { border-color: var(--first-color); }

    .toast {
      position: fixed;
      bottom: 1.5rem;
      right: 1.5rem;
      background: #323232;
      color: #fff;
      padding: .6rem 1.1rem;
      border-radius: var(--radius);
      font-size: var(--small-font);
      z-index: 3000;
      opacity: 0;
      transform: translateY(8px);
      transition: opacity .2s, transform .2s;
      pointer-events: none;
    }

    .toast.show { opacity: 1; transform: none; }
    .toast.success { background: #2e7d32; }
    .toast.error   { background: #c62828; }

    /* ── Order items inside modal ────────────────────────────────── */
    .items-section { margin-top: .2rem; }

    .items-section .modal__label { margin-bottom: .6rem; }

    .items-loading {
      display: flex;
      align-items: center;
      gap: .5rem;
      color: var(--text-color-light);
      font-size: var(--small-font);
      padding: .5rem 0;
    }

    .items-loading .spin {
      display: inline-block;
      animation: spin 1s linear infinite;
      font-style: normal;
    }

    .order-item-row {
      display: flex;
      align-items: center;
      gap: .85rem;
      padding: .65rem 0;
      border-bottom: 1px solid var(--border-color);
    }

    .order-item-row:last-child { border-bottom: none; }

    .order-item-img {
      width: 46px;
      height: 46px;
      border-radius: 6px;
      object-fit: cover;
      flex-shrink: 0;
      background: var(--body-color);
      border: 1px solid var(--border-color);
    }

    .order-item-img-placeholder {
      width: 46px;
      height: 46px;
      border-radius: 6px;
      background: #fce4ec;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.2rem;
      flex-shrink: 0;
      border: 1px solid var(--border-color);
    }

    .order-item-info { flex: 1; min-width: 0; }

    .order-item-name {
      font-size: var(--small-font);
      font-weight: 600;
      color: var(--title-color);
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }

    .order-item-meta {
      font-size: .72rem;
      color: var(--text-color-light);
      margin-top: .15rem;
      display: flex;
      flex-wrap: wrap;
      gap: .4rem;
    }

    .order-item-meta span { display: inline-flex; align-items: center; gap: .2rem; }

    .order-item-price {
      text-align: right;
      flex-shrink: 0;
    }

    .order-item-price .line-total {
      font-weight: 700;
      font-size: var(--small-font);
      color: var(--title-color);
    }

    .order-item-price .unit-price {
      font-size: .7rem;
      color: var(--text-color-light);
    }

    .items-summary {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: .7rem 0 0;
      margin-top: .3rem;
      border-top: 2px solid var(--border-color);
      font-size: var(--small-font);
    }

    .items-summary .summary-label { color: var(--text-color-light); }

    .items-summary .summary-total {
      font-weight: 700;
      font-size: 1rem;
      color: var(--title-color);
    }

    .items-summary .summary-shipping {
      font-size: .72rem;
      color: var(--text-color-light);
      text-align: right;
    }
  </style>
</head>
<body>

<div class="wrapper">

  <!-- ── Sidebar ─────────────────────────────────────────────────── -->
  <aside class="sidebar">
    <div class="sidebar__brand">
      <span>🧂</span> The Salty
    </div>
    <nav class="sidebar__nav" id="sideNav">
      <div class="nav-item active" data-view="overview">
        <span class="icon">📊</span> Overview
      </div>
      <div class="nav-item" data-view="orders">
        <span class="icon">🛒</span> Orders
      </div>
      <div class="nav-item" data-view="products">
        <span class="icon">📦</span> Products
      </div>
      <div class="nav-item" data-view="customers">
        <span class="icon">👥</span> Customers
      </div>
      <div class="nav-item" data-view="coupons">
        <span class="icon">🎟️</span> Coupons
      </div>
    </nav>
  </aside>

  <!-- ── Main ────────────────────────────────────────────────────── -->
  <div class="main">

    <!-- Top bar -->
    <div class="topbar">
      <div style="display:flex;align-items:center;gap:.75rem;">
        <a href="../home" style="text-decoration:none;font-size:1.1rem;display:flex;align-items:center;gap:.3rem;" title="Back to Shop">
          🏠 <span style="font-size:.85rem;color:var(--text-color-light);font-weight:500;">Shop Home</span>
        </a>
        <span style="color:var(--border-color-alt);font-size:.9rem;">|</span>
        <h1 id="pageTitle">Overview</h1>
      </div>
      <div style="display:flex;align-items:center;gap:.8rem;" class="topbar__actions" id="topbarActions">
        <div class="range-btns" id="rangeBtns">
          <button class="range-btn" data-range="7">7D</button>
          <button class="range-btn active" data-range="30">30D</button>
          <button class="range-btn" data-range="90">90D</button>
          <button class="range-btn" data-range="365">1Y</button>
        </div>
        <button class="btn-refresh" id="refreshBtn">
          <span class="spin">↻</span> Refresh
        </button>
      </div>
    </div>

    <!-- Content area -->
    <div class="content" id="content">
      <!-- Dynamically rendered -->
    </div>

  </div><!-- /.main -->

</div><!-- /.wrapper -->

<!-- ── Order Detail / Edit Modal ────────────────────────────────── -->
<div class="modal-overlay" id="orderModal" role="dialog" aria-modal="true" aria-labelledby="modalTitle">
  <div class="modal">
    <div class="modal__header">
      <span class="modal__title" id="modalTitle">Order #<span id="modalOrderId"></span></span>
      <button class="modal__close" id="modalClose" aria-label="Close">✕</button>
    </div>
    <div class="modal__body">
      <div class="modal__grid">
        <div class="modal__section">
          <div class="modal__label">Customer</div>
          <div class="modal__value" id="modalCustomer">—</div>
        </div>
        <div class="modal__section">
          <div class="modal__label">Date</div>
          <div class="modal__value" id="modalDate">—</div>
        </div>
        <div class="modal__section">
          <div class="modal__label">Email</div>
          <div class="modal__value" id="modalEmail">—</div>
        </div>
        <div class="modal__section">
          <div class="modal__label">Phone</div>
          <div class="modal__value" id="modalPhone">—</div>
        </div>
        <div class="modal__section">
          <div class="modal__label">Order Total</div>
          <div class="modal__value" id="modalTotal">—</div>
        </div>
        <div class="modal__section">
          <div class="modal__label">Shipping</div>
          <div class="modal__value" id="modalShipping">—</div>
        </div>
      </div>

      <hr style="border:none;border-top:1px solid var(--border-color);margin:1rem 0;" />

      <div class="modal__grid">
        <div class="modal__section">
          <div class="modal__label">Order Status</div>
          <select id="modalOrderStatus" class="form-select">
            <option value="Pending">Pending</option>
            <option value="Completed">Completed</option>
            <option value="Canceled">Canceled</option>
          </select>
        </div>
        <div class="modal__section">
          <div class="modal__label">Payment Status</div>
          <select id="modalPaymentStatus" class="form-select">
            <option value="0">Unpaid</option>
            <option value="1">Paid</option>
          </select>
        </div>
      </div>

      <hr style="border:none;border-top:1px solid var(--border-color);margin:1rem 0;" />

      <!-- Order Items -->
      <div class="items-section">
        <div class="modal__label">Order Items</div>
        <div id="modalItemsList">
          <div class="items-loading"><i class="spin">↻</i> Loading items…</div>
        </div>
      </div>
    </div>
    <div class="modal__footer">
      <button class="btn-secondary" id="modalCancelBtn">Cancel</button>
      <button class="btn-primary"   id="modalSaveBtn">Save Changes</button>
    </div>
  </div>
</div>

<!-- Toast notification -->
<div class="toast" id="toast"></div>

<!-- Mobile bottom nav -->
<nav class="mobile-nav" id="mobileNav">
  <div class="mobile-nav__item active" data-view="overview"><span class="icon">📊</span>Overview</div>
  <div class="mobile-nav__item" data-view="orders"><span class="icon">🛒</span>Orders</div>
  <div class="mobile-nav__item" data-view="products"><span class="icon">📦</span>Products</div>
  <div class="mobile-nav__item" data-view="customers"><span class="icon">👥</span>Customers</div>
  <div class="mobile-nav__item" data-view="coupons"><span class="icon">🎟️</span>Coupons</div>
</nav>

<!-- ── JavaScript ─────────────────────────────────────────────────── -->
<script>
/* ─ state ─ */
let currentView  = 'overview';
let currentRange = 30;
const charts     = {};

/* ─ utility ─ */
const $ = id => document.getElementById(id);
const fmt_currency = v => '₹' + Number(v).toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2});
const fmt_number   = v => Number(v).toLocaleString('en-IN');

async function api(section, range) {
  const r = await fetch(`dashboard_data.php?section=${section}&range=${range}`);
  if (!r.ok) throw new Error(`HTTP ${r.status}`);
  return r.json();
}

function destroyChart(id) {
  if (charts[id]) { charts[id].destroy(); delete charts[id]; }
}

/* ─ render helpers ─ */
function skeleton(rows = 4) {
  return Array.from({length: rows}, () => `<div class="skeleton"></div>`).join('');
}

function renderCard(title, bodyHtml, headerExtra = '') {
  return `
    <div class="card">
      <div class="card__header">
        <span class="card__title">${title}</span>
        ${headerExtra}
      </div>
      <div class="card__body">${bodyHtml}</div>
    </div>`;
}

/* ══════════════════════════════════
   VIEW: OVERVIEW
══════════════════════════════════ */
async function renderOverview(range) {
  $('content').innerHTML = `
    <div class="kpi-grid" id="kpiGrid">${Array(6).fill('<div class="kpi-card"><div class="skeleton"></div><div class="skeleton"></div></div>').join('')}</div>
    <div class="chart-row">
      ${renderCard('Revenue Over Time', `<div class="chart-wrap"><canvas id="revenueChart"></canvas></div>`)}
      ${renderCard('Order Status', `<div class="chart-wrap"><canvas id="statusChart"></canvas></div>`)}
    </div>
    <div class="chart-row">
      ${renderCard('Sales by Category', `<div class="chart-wrap"><canvas id="categoryChart"></canvas></div>`)}
      ${renderCard('Customer Acquisition', `<div class="chart-wrap"><canvas id="acqChart"></canvas></div>`)}
    </div>`;

  // KPIs
  try {
    const kpi = await api('overview', range);
    const growth = kpi.revenue_growth;
    const growthHtml = growth === null
      ? `<span class="neutral">No prior data</span>`
      : growth >= 0
        ? `<span class="up">▲ ${growth}% vs prev period</span>`
        : `<span class="down">▼ ${Math.abs(growth)}% vs prev period</span>`;

    $('kpiGrid').innerHTML = `
      <div class="kpi-card">
        <div class="kpi-card__label">Revenue</div>
        <div class="kpi-card__value">${fmt_currency(kpi.revenue)}</div>
        <div class="kpi-card__sub">${growthHtml}</div>
      </div>
      <div class="kpi-card">
        <div class="kpi-card__label">Orders</div>
        <div class="kpi-card__value">${fmt_number(kpi.orders)}</div>
        <div class="kpi-card__sub neutral">Paid & completed</div>
      </div>
      <div class="kpi-card">
        <div class="kpi-card__label">Avg Order Value</div>
        <div class="kpi-card__value">${fmt_currency(kpi.aov)}</div>
        <div class="kpi-card__sub neutral">Per paid order</div>
      </div>
      <div class="kpi-card">
        <div class="kpi-card__label">New Customers</div>
        <div class="kpi-card__value">${fmt_number(kpi.new_users)}</div>
        <div class="kpi-card__sub neutral">Registrations</div>
      </div>
      <div class="kpi-card">
        <div class="kpi-card__label">Pending Orders</div>
        <div class="kpi-card__value">${fmt_number(kpi.pending_orders)}</div>
        <div class="kpi-card__sub ${kpi.pending_orders > 0 ? 'down' : 'up'}">
          ${kpi.pending_orders > 0 ? 'Needs attention' : 'All clear'}
        </div>
      </div>`;
  } catch(e) {
    $('kpiGrid').innerHTML = `<div class="error-msg">Failed to load KPIs: ${e.message}</div>`;
  }

  // Revenue chart
  try {
    const rev = await api('revenue_chart', range);
    destroyChart('revenueChart');
    charts['revenueChart'] = new Chart($('revenueChart'), {
      type: 'line',
      data: {
        labels: rev.map(r => r.period),
        datasets: [{
          label: 'Revenue (₹)',
          data: rev.map(r => parseFloat(r.revenue)),
          borderColor: '#d32f2f',
          backgroundColor: 'rgba(211,47,47,.08)',
          tension: .35,
          fill: true,
          pointRadius: rev.length > 40 ? 0 : 3,
        }]
      },
      options: chartOptions('₹')
    });
  } catch(e) { console.error(e); }

  // Status donut
  try {
    const status = await api('order_status', range);
    destroyChart('statusChart');
    const COLORS = { Pending: '#ff9800', Completed: '#4caf50', Canceled: '#f44336' };
    charts['statusChart'] = new Chart($('statusChart'), {
      type: 'doughnut',
      data: {
        labels: status.map(r => r.status),
        datasets: [{
          data: status.map(r => r.cnt),
          backgroundColor: status.map(r => COLORS[r.status] || '#9e9e9e'),
          borderWidth: 2,
          borderColor: '#fff',
        }]
      },
      options: {
        responsive: true, maintainAspectRatio: false,
        plugins: { legend: { position: 'bottom', labels: { font: { family: 'Poppins', size: 11 } } } }
      }
    });
  } catch(e) { console.error(e); }

  // Category bar
  try {
    const cats = await api('category_sales', range);
    destroyChart('categoryChart');
    charts['categoryChart'] = new Chart($('categoryChart'), {
      type: 'bar',
      data: {
        labels: cats.map(r => r.category),
        datasets: [{
          label: 'Revenue (₹)',
          data: cats.map(r => parseFloat(r.revenue)),
          backgroundColor: '#d32f2f',
          borderRadius: 4,
        }]
      },
      options: { ...chartOptions('₹'), indexAxis: 'y' }
    });
  } catch(e) { console.error(e); }

  // Acquisition pie
  try {
    const acq = await api('acquisition', range);
    destroyChart('acqChart');
    charts['acqChart'] = new Chart($('acqChart'), {
      type: 'pie',
      data: {
        labels: acq.map(r => r.source),
        datasets: [{
          data: acq.map(r => r.cnt),
          backgroundColor: ['#d32f2f','#e57373','#ffcdd2','#ef9a9a','#b71c1c','#c62828'],
          borderWidth: 2,
          borderColor: '#fff',
        }]
      },
      options: {
        responsive: true, maintainAspectRatio: false,
        plugins: { legend: { position: 'bottom', labels: { font: { family: 'Poppins', size: 11 } } } }
      }
    });
  } catch(e) { console.error(e); }
}

/* ══════════════════════════════════
   VIEW: ORDERS  (inline edit + modal)
══════════════════════════════════ */
let ordersCache = []; // keep a live copy for in-place updates

async function renderOrders(range) {
  $('content').innerHTML = `
    <div class="chart-row" style="margin-bottom:1.5rem;">
      ${renderCard('Revenue Trend', `<div class="chart-wrap"><canvas id="ordRevChart"></canvas></div>`)}
      ${renderCard('Order Status Breakdown', `<div class="chart-wrap"><canvas id="ordStatusChart"></canvas></div>`)}
    </div>
    <div class="card">
      <div class="card__header">
        <span class="card__title">All Orders</span>
        <span style="font-size:var(--small-font);color:var(--text-color-light)" id="ordersCount"></span>
      </div>
      <div class="card__body">
        <div class="orders-toolbar">
          <input type="text" id="orderSearch" placeholder="Search by ID or customer…" />
          <select class="filter-select" id="statusFilter">
            <option value="">All Statuses</option>
            <option value="Pending">Pending</option>
            <option value="Completed">Completed</option>
            <option value="Canceled">Canceled</option>
          </select>
          <button class="btn-refresh" id="ordersRefreshBtn" style="margin-left:auto;">
            <span class="spin">↻</span> Refresh
          </button>
        </div>
        <div id="ordersTableWrap">${skeleton(6)}</div>
      </div>
    </div>`;

  // Charts
  try {
    const rev = await api('revenue_chart', range);
    destroyChart('ordRevChart');
    charts['ordRevChart'] = new Chart($('ordRevChart'), {
      type: 'bar',
      data: {
        labels: rev.map(r => r.period),
        datasets: [
          { label: 'Revenue (₹)', data: rev.map(r => parseFloat(r.revenue)), backgroundColor: '#d32f2f', borderRadius: 3, yAxisID: 'y' },
          { label: 'Orders', data: rev.map(r => parseInt(r.orders)), type: 'line', borderColor: '#c62828', backgroundColor: 'transparent', tension: .3, yAxisID: 'y1' }
        ]
      },
      options: {
        responsive: true, maintainAspectRatio: false,
        plugins: { legend: { labels: { font: { family: 'Poppins', size: 11 } } } },
        scales: {
          y:  { type: 'linear', position: 'left',  ticks: { callback: v => '₹' + fmt_number(v), font: { family: 'Poppins', size: 10 } } },
          y1: { type: 'linear', position: 'right', grid: { drawOnChartArea: false }, ticks: { font: { family: 'Poppins', size: 10 } } },
          x:  { ticks: { font: { family: 'Poppins', size: 10 } } }
        }
      }
    });
  } catch(e) { console.error(e); }

  try {
    const status = await api('order_status', range);
    destroyChart('ordStatusChart');
    const COLORS = { Pending: '#ff9800', Completed: '#4caf50', Canceled: '#f44336' };
    charts['ordStatusChart'] = new Chart($('ordStatusChart'), {
      type: 'doughnut',
      data: {
        labels: status.map(r => r.status),
        datasets: [{ data: status.map(r => r.cnt), backgroundColor: status.map(r => COLORS[r.status] || '#9e9e9e'), borderWidth: 2, borderColor: '#fff' }]
      },
      options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom', labels: { font: { family: 'Poppins', size: 11 } } } } }
    });
  } catch(e) { console.error(e); }

  // Load table
  await loadOrdersTable();

  // Toolbar events
  let searchTimer;
  $('orderSearch').addEventListener('input', () => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => loadOrdersTable(), 350);
  });
  $('statusFilter').addEventListener('change', () => loadOrdersTable());
  $('ordersRefreshBtn').addEventListener('click', () => loadOrdersTable());
}

async function loadOrdersTable() {
  const search = ($('orderSearch')?.value || '').trim();
  const filter = $('statusFilter')?.value || '';
  const wrap   = $('ordersTableWrap');
  if (!wrap) return;
  wrap.innerHTML = skeleton(6);

  try {
    const url = `dashboard_data.php?section=recent_orders&limit=100`
      + (search ? `&search=${encodeURIComponent(search)}` : '')
      + (filter ? `&status_filter=${encodeURIComponent(filter)}` : '');
    const r = await fetch(url);
    if (!r.ok) throw new Error(`HTTP ${r.status}`);
    ordersCache = await r.json();
    renderOrdersTable(ordersCache);
  } catch(e) {
    wrap.innerHTML = `<div class="error-msg">Failed to load orders: ${e.message}</div>`;
  }
}

function renderOrdersTable(rows) {
  const wrap = $('ordersTableWrap');
  if (!wrap) return;
  const count = $('ordersCount');
  if (count) count.textContent = `${rows.length} order${rows.length !== 1 ? 's' : ''}`;

  if (!rows.length) {
    wrap.innerHTML = '<p style="padding:.5rem;color:var(--text-color-light)">No orders found.</p>';
    return;
  }

  wrap.innerHTML = `
    <div style="overflow-x:auto;">
      <table class="data-table" id="ordersTable">
        <thead>
          <tr>
            <th>#ID</th>
            <th>Customer</th>
            <th>Amount</th>
            <th>Order Status</th>
            <th>Payment</th>
            <th>Date</th>
            <th style="width:40px"></th>
          </tr>
        </thead>
        <tbody>
          ${rows.map(r => orderRow(r)).join('')}
        </tbody>
      </table>
    </div>`;

  // Wire up inline controls
  wrap.querySelectorAll('.status-select').forEach(sel => {
    sel.addEventListener('change', function() {
      const id = parseInt(this.dataset.id);
      updateStyleClass(this);
      saveOrderField(id, 'order_status', this.value);
    });
  });

  wrap.querySelectorAll('.payment-toggle input').forEach(cb => {
    cb.addEventListener('change', function() {
      const id    = parseInt(this.dataset.id);
      const label = this.closest('.payment-toggle');
      label.classList.toggle('paid', this.checked);
      label.classList.toggle('unpaid', !this.checked);
      label.querySelector('.toggle-label').textContent = this.checked ? 'Paid' : 'Unpaid';
      saveOrderField(id, 'payment_status', this.checked ? 1 : 0);
    });
  });

  // Wire up row click to open order details modal (excluding interactive controls)
  wrap.querySelectorAll('#ordersTable tbody tr').forEach(row => {
    row.classList.add('clickable-row');
    row.addEventListener('click', function(e) {
      if (e.target.closest('.status-select') || e.target.closest('.payment-toggle')) {
        return;
      }
      const id = parseInt(this.id.replace('order-row-', ''));
      openOrderModal(id);
    });
  });
}

function orderRow(r) {
  const statusClass = `s-${r.status.toLowerCase()}`;
  const isPaid = parseInt(r.paid) === 1;
  return `
    <tr id="order-row-${r.OrderId}">
      <td><strong>#${r.OrderId}</strong></td>
      <td>${escHtml(r.customer)}</td>
      <td>${fmt_currency(r.TotalAmount)}</td>
      <td>
        <select class="status-select ${statusClass}" data-id="${r.OrderId}" title="Change order status">
          <option value="Pending"   ${r.status==='Pending'   ? 'selected':''}>Pending</option>
          <option value="Completed" ${r.status==='Completed' ? 'selected':''}>Completed</option>
          <option value="Canceled"  ${r.status==='Canceled'  ? 'selected':''}>Canceled</option>
        </select>
      </td>
      <td>
        <label class="payment-toggle ${isPaid ? 'paid' : 'unpaid'}" title="Toggle payment status">
          <input type="checkbox" data-id="${r.OrderId}" ${isPaid ? 'checked' : ''} />
          <span class="toggle-track"></span>
          <span class="toggle-label">${isPaid ? 'Paid' : 'Unpaid'}</span>
        </label>
      </td>
      <td>${r.CreatedAt.slice(0, 16)}</td>
      <td style="text-align: center;">
        <span class="order-view-indicator" title="View details" style="font-size: 1.15rem; cursor: pointer; color: var(--first-color);">
          👁️
        </span>
      </td>
    </tr>`;
}

function updateStyleClass(sel) {
  sel.className = `status-select s-${sel.value.toLowerCase()}`;
}

/* ── API: save a single field inline ─ */
async function saveOrderField(orderId, field, value) {
  const row = $(`order-row-${orderId}`);
  if (row) row.classList.add('row-saving');

  try {
    const body = { order_id: orderId };
    if (field === 'order_status')   body.order_status   = value;
    if (field === 'payment_status') body.payment_status = value;

    const res  = await fetch('dashboard_data.php?section=update_order', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(body)
    });
    const data = await res.json();

    if (data.success) {
      // Update local cache
      const idx = ordersCache.findIndex(o => o.OrderId == orderId);
      if (idx > -1 && data.order) ordersCache[idx] = { ...ordersCache[idx], ...data.order };
      showToast('Order #' + orderId + ' updated', 'success');
    } else {
      showToast(data.error || 'Update failed', 'error');
      // Revert UI by re-rendering the row
      const idx = ordersCache.findIndex(o => o.OrderId == orderId);
      if (idx > -1 && row) {
        row.outerHTML = orderRow(ordersCache[idx]);
        rewireRow(orderId);
      }
    }
  } catch(e) {
    showToast('Network error: ' + e.message, 'error');
  } finally {
    const r2 = $(`order-row-${orderId}`);
    if (r2) r2.classList.remove('row-saving');
  }
}

function rewireRow(orderId) {
  const row = $(`order-row-${orderId}`);
  if (!row) return;
  row.querySelector('.status-select')?.addEventListener('change', function() {
    updateStyleClass(this);
    saveOrderField(orderId, 'order_status', this.value);
  });
  const cb = row.querySelector('.payment-toggle input');
  if (cb) cb.addEventListener('change', function() {
    const label = this.closest('.payment-toggle');
    label.classList.toggle('paid', this.checked);
    label.classList.toggle('unpaid', !this.checked);
    label.querySelector('.toggle-label').textContent = this.checked ? 'Paid' : 'Unpaid';
    saveOrderField(orderId, 'payment_status', this.checked ? 1 : 0);
  });
  row.querySelector('.order-detail-btn')?.addEventListener('click', () => openOrderModal(orderId));
}

/* ── Modal ─ */
let modalOrderId = null;

function openOrderModal(orderId) {
  const order = ordersCache.find(o => o.OrderId == orderId);
  if (!order) return;
  modalOrderId = orderId;

  $('modalOrderId').textContent    = orderId;
  $('modalCustomer').textContent   = order.customer || '—';
  $('modalEmail').textContent      = order.email    || '—';
  $('modalPhone').textContent      = order.phone    || '—';
  $('modalDate').textContent       = (order.CreatedAt || '').slice(0, 16);
  $('modalTotal').textContent      = fmt_currency(order.TotalAmount);
  $('modalShipping').textContent   = order.ShippingAmount ? fmt_currency(order.ShippingAmount) : '—';
  $('modalOrderStatus').value      = order.status;
  $('modalPaymentStatus').value    = String(order.paid);

  // Reset items area and kick off fetch
  $('modalItemsList').innerHTML = `<div class="items-loading"><i class="spin">↻</i> Loading items…</div>`;

  $('orderModal').classList.add('open');
  $('modalSaveBtn').disabled = false;
  $('modalSaveBtn').textContent = 'Save Changes';

  loadOrderItems(orderId, order);
}

async function loadOrderItems(orderId, order) {
  try {
    const res   = await fetch(`dashboard_data.php?section=order_items&order_id=${orderId}`);
    if (!res.ok) throw new Error(`HTTP ${res.status}`);
    const items = await res.json();
    renderOrderItems(items, order);
  } catch(e) {
    $('modalItemsList').innerHTML = `<div class="error-msg">Could not load items: ${e.message}</div>`;
  }
}

function renderOrderItems(items, order) {
  const el = $('modalItemsList');
  if (!el) return;

  if (!items.length) {
    el.innerHTML = `<p style="color:var(--text-color-light);font-size:var(--small-font);padding:.4rem 0">No items found for this order.</p>`;
    return;
  }

  const subtotal = items.reduce((s, i) => s + parseFloat(i.line_total), 0);
  const shipping  = order?.ShippingAmount ? parseFloat(order.ShippingAmount) : 0;

  const rows = items.map(item => {
    const hasImg   = item.image && item.image.trim() !== '';
    const imgHtml  = hasImg
      ? `<img class="order-item-img" src="${escHtml(item.image)}" alt="${escHtml(item.name)}" onerror="this.style.display='none';this.nextElementSibling.style.display='flex'" /><div class="order-item-img-placeholder" style="display:none">📦</div>`
      : `<div class="order-item-img-placeholder">📦</div>`;

    const customHtml = item.Customization
      ? `<span title="Customization">✏️ ${escHtml(item.Customization)}</span>`
      : '';

    return `
      <div class="order-item-row">
        ${imgHtml}
        <div class="order-item-info">
          <div class="order-item-name" title="${escHtml(item.name)}">${escHtml(item.name)}</div>
          <div class="order-item-meta">
            <span>SKU: ${escHtml(item.sku)}</span>
            <span>Qty: <strong>${item.qty}</strong></span>
            ${customHtml}
          </div>
        </div>
        <div class="order-item-price">
          <div class="line-total">${fmt_currency(item.line_total)}</div>
          <div class="unit-price">${fmt_currency(item.unit_price)} each</div>
        </div>
      </div>`;
  }).join('');

  const shippingRow = shipping > 0
    ? `<div style="display:flex;justify-content:space-between;font-size:.75rem;color:var(--text-color-light);padding:.35rem 0;">
         <span>Shipping</span><span>${fmt_currency(shipping)}</span>
       </div>`
    : '';

  el.innerHTML = `
    ${rows}
    <div class="items-summary">
      <div>
        <div class="summary-label">${items.length} item${items.length !== 1 ? 's' : ''}</div>
        ${shippingRow ? `<div style="font-size:.72rem;color:var(--text-color-light);margin-top:.15rem">+ shipping included</div>` : ''}
      </div>
      <div style="text-align:right;">
        ${shippingRow}
        <div class="summary-total">${fmt_currency(order?.TotalAmount ?? subtotal + shipping)}</div>
      </div>
    </div>`;
}

function closeOrderModal() {
  $('orderModal').classList.remove('open');
  modalOrderId = null;
}

$('modalClose').addEventListener('click', closeOrderModal);
$('modalCancelBtn').addEventListener('click', closeOrderModal);
$('orderModal').addEventListener('click', e => { if (e.target === $('orderModal')) closeOrderModal(); });

$('modalSaveBtn').addEventListener('click', async () => {
  if (!modalOrderId) return;
  const saveBtn = $('modalSaveBtn');
  saveBtn.disabled = true;
  saveBtn.textContent = 'Saving…';

  const newStatus  = $('modalOrderStatus').value;
  const newPayment = parseInt($('modalPaymentStatus').value);

  try {
    const res  = await fetch('dashboard_data.php?section=update_order', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ order_id: modalOrderId, order_status: newStatus, payment_status: newPayment })
    });
    const data = await res.json();

    if (data.success) {
      // Patch cache
      const idx = ordersCache.findIndex(o => o.OrderId == modalOrderId);
      if (idx > -1 && data.order) ordersCache[idx] = { ...ordersCache[idx], ...data.order };

      // Re-render the row in the table
      const row = $(`order-row-${modalOrderId}`);
      if (row && idx > -1) {
        row.outerHTML = orderRow(ordersCache[idx]);
        rewireRow(modalOrderId);
      }

      showToast(`Order #${modalOrderId} saved`, 'success');
      closeOrderModal();
    } else {
      showToast(data.error || 'Save failed', 'error');
      saveBtn.disabled = false;
      saveBtn.textContent = 'Save Changes';
    }
  } catch(e) {
    showToast('Network error', 'error');
    saveBtn.disabled = false;
    saveBtn.textContent = 'Save Changes';
  }
});

/* ── Toast ─ */
let toastTimer;
function showToast(msg, type = 'success') {
  const t = $('toast');
  t.textContent = msg;
  t.className   = `toast ${type} show`;
  clearTimeout(toastTimer);
  toastTimer = setTimeout(() => t.classList.remove('show'), 3000);
}

/* ══════════════════════════════════
   VIEW: PRODUCTS
══════════════════════════════════ */
async function renderProducts(range) {
  $('content').innerHTML = `
    <div class="chart-row" style="margin-bottom:1.5rem;">
      ${renderCard('Top Products by Revenue', `<div id="topProds">${skeleton(6)}</div>`)}
      ${renderCard('Sales by Category', `<div class="chart-wrap"><canvas id="catProdChart"></canvas></div>`)}
    </div>
    ${renderCard('Low Stock Alert', `<div id="lowStock">${skeleton(4)}</div>`)}`;

  // Top products
  try {
    const prods = await api('top_products', range);
    if (!prods.length) { $('topProds').innerHTML = '<p style="color:var(--text-color-light);padding:.5rem">No data.</p>'; }
    else {
      const max = Math.max(...prods.map(p => parseFloat(p.revenue)));
      $('topProds').innerHTML = `
        <div style="overflow-x:auto;">
          <table class="data-table">
            <thead><tr><th>Product</th><th>Units</th><th>Revenue</th><th style="width:120px">Share</th></tr></thead>
            <tbody>
            ${prods.map(p => {
              const pct = max > 0 ? Math.round((parseFloat(p.revenue)/max)*100) : 0;
              return `<tr>
                <td>${escHtml(p.name)}</td>
                <td>${fmt_number(p.units_sold)}</td>
                <td>${fmt_currency(p.revenue)}</td>
                <td><div class="bar-fill"><div class="bar-fill__inner" style="width:${pct}%"></div></div></td>
              </tr>`;
            }).join('')}
            </tbody>
          </table>
        </div>`;
    }
  } catch(e) { $('topProds').innerHTML = `<div class="error-msg">${e.message}</div>`; }

  // Category chart
  try {
    const cats = await api('category_sales', range);
    destroyChart('catProdChart');
    charts['catProdChart'] = new Chart($('catProdChart'), {
      type: 'doughnut',
      data: {
        labels: cats.map(r => r.category),
        datasets: [{
          data: cats.map(r => parseFloat(r.revenue)),
          backgroundColor: ['#d32f2f','#e57373','#ffcdd2','#ef9a9a','#b71c1c','#c62828','#ff8a80'],
          borderWidth: 2,
          borderColor: '#fff',
        }]
      },
      options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom', labels: { font: { family: 'Poppins', size: 11 } } } } }
    });
  } catch(e) { console.error(e); }

  // Low stock
  try {
    const items = await api('low_stock', range);
    if (!items.length) {
      $('lowStock').innerHTML = '<p style="color:#2e7d32;padding:.5rem">✅ All products are adequately stocked.</p>';
    } else {
      $('lowStock').innerHTML = `
        <div style="overflow-x:auto;">
          <table class="data-table">
            <thead><tr><th>Product</th><th>SKU</th><th>Stock</th><th>Status</th></tr></thead>
            <tbody>
            ${items.map(i => `
              <tr>
                <td>${escHtml(i.name)}</td>
                <td><code>${escHtml(i.sku)}</code></td>
                <td><strong style="color:${i.stock <= 3 ? '#c62828' : '#e65100'}">${i.stock}</strong></td>
                <td><span class="badge badge--${i.stock <= 3 ? 'low' : 'pending'}">${i.stock <= 3 ? 'Critical' : 'Low'}</span></td>
              </tr>`).join('')}
            </tbody>
          </table>
        </div>`;
    }
  } catch(e) { $('lowStock').innerHTML = `<div class="error-msg">${e.message}</div>`; }
}

/* ══════════════════════════════════
   VIEW: CUSTOMERS
══════════════════════════════════ */
async function renderCustomers(range) {
  $('content').innerHTML = `
    <div class="chart-row">
      ${renderCard('Customer Acquisition Source', `<div class="chart-wrap"><canvas id="acqBar"></canvas></div>`)}
      ${renderCard('Acquisition Mix', `<div class="chart-wrap"><canvas id="acqPie"></canvas></div>`)}
    </div>`;

  try {
    const acq = await api('acquisition', range);
    destroyChart('acqBar');
    charts['acqBar'] = new Chart($('acqBar'), {
      type: 'bar',
      data: {
        labels: acq.map(r => r.source),
        datasets: [{ label: 'Customers', data: acq.map(r => r.cnt), backgroundColor: '#d32f2f', borderRadius: 4 }]
      },
      options: { ...chartOptions(), indexAxis: 'y' }
    });

    destroyChart('acqPie');
    charts['acqPie'] = new Chart($('acqPie'), {
      type: 'pie',
      data: {
        labels: acq.map(r => r.source),
        datasets: [{ data: acq.map(r => r.cnt), backgroundColor: ['#d32f2f','#e57373','#ffcdd2','#ef9a9a','#b71c1c','#c62828'], borderWidth: 2, borderColor: '#fff' }]
      },
      options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom', labels: { font: { family: 'Poppins', size: 11 } } } } }
    });
  } catch(e) {
    $('content').innerHTML += `<div class="error-msg">${e.message}</div>`;
  }
}

/* ══════════════════════════════════
   VIEW: COUPONS
══════════════════════════════════ */
async function renderCoupons(range) {
  $('content').innerHTML = renderCard('Coupon Performance', `<div id="couponTable">${skeleton(5)}</div>`);
  try {
    const coupons = await api('coupons', range);
    if (!coupons.length) { $('couponTable').innerHTML = '<p style="color:var(--text-color-light)">No coupons found.</p>'; return; }
    $('couponTable').innerHTML = `
      <div style="overflow-x:auto;">
        <table class="data-table">
          <thead><tr>
            <th>Code</th><th>Type</th><th>Value</th><th>Uses</th><th>Max</th><th>Expires</th><th>Stackable</th>
          </tr></thead>
          <tbody>
          ${coupons.map(c => {
            const valStr = c.type === 'percentage' ? c.discount_value + '%'
                         : c.type === 'free_shipping' ? 'Free shipping'
                         : fmt_currency(c.discount_value);
            const expired = c.expiration_date && new Date(c.expiration_date) < new Date();
            return `<tr>
              <td><strong>${escHtml(c.code)}</strong></td>
              <td>${c.type.replace('_',' ')}</td>
              <td>${valStr}</td>
              <td>${c.uses}</td>
              <td>${c.max_uses ?? '∞'}</td>
              <td><span class="badge badge--${expired ? 'canceled' : 'completed'}">${c.expiration_date ? c.expiration_date.slice(0,10) : 'No expiry'}</span></td>
              <td>${c.stackable ? '✅' : '—'}</td>
            </tr>`;
          }).join('')}
          </tbody>
        </table>
      </div>`;
  } catch(e) { $('couponTable').innerHTML = `<div class="error-msg">${e.message}</div>`; }
}

/* ─ shared chart options ─ */
function chartOptions(prefix = '') {
  return {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
      legend: { display: false },
      tooltip: {
        callbacks: {
          label: ctx => (prefix ? prefix : '') + ' ' + fmt_number(ctx.parsed.y ?? ctx.parsed.x ?? ctx.raw)
        }
      }
    },
    scales: {
      x: { ticks: { font: { family: 'Poppins', size: 10 }, maxRotation: 45 }, grid: { display: false } },
      y: { ticks: { font: { family: 'Poppins', size: 10 }, callback: v => prefix + fmt_number(v) }, grid: { color: '#f0f0f0' } }
    }
  };
}

function escHtml(str) {
  const d = document.createElement('div');
  d.appendChild(document.createTextNode(str || ''));
  return d.innerHTML;
}

/* ─ navigation ─ */
const viewTitles = { overview: 'Overview', orders: 'Orders', products: 'Products', customers: 'Customers', coupons: 'Coupons' };
const viewFns    = { overview: renderOverview, orders: renderOrders, products: renderProducts, customers: renderCustomers, coupons: renderCoupons };

function setView(view, range) {
  currentView = view;
  $('pageTitle').textContent = viewTitles[view] || view;

  // Update sidebar
  document.querySelectorAll('#sideNav .nav-item, #mobileNav .mobile-nav__item').forEach(el => {
    el.classList.toggle('active', el.dataset.view === view);
  });

  // Range buttons hidden on coupons/customers (not time-relevant)
  $('rangeBtns').style.visibility = ['customers','coupons'].includes(view) ? 'hidden' : 'visible';

  viewFns[view]?.(range);
}

/* ─ event wiring ─ */
document.querySelectorAll('#sideNav .nav-item, #mobileNav .mobile-nav__item').forEach(el => {
  el.addEventListener('click', () => setView(el.dataset.view, currentRange));
});

document.querySelectorAll('.range-btn').forEach(btn => {
  btn.addEventListener('click', () => {
    currentRange = parseInt(btn.dataset.range);
    document.querySelectorAll('.range-btn').forEach(b => b.classList.toggle('active', b === btn));
    setView(currentView, currentRange);
  });
});

$('refreshBtn').addEventListener('click', () => {
  const btn = $('refreshBtn');
  btn.classList.add('loading');
  setView(currentView, currentRange);
  setTimeout(() => btn.classList.remove('loading'), 800);
});

/* ─ push notifications subscription ─ */
function urlB64ToUint8Array(base64String) {
  const padding = '='.repeat((4 - base64String.length % 4) % 4);
  const base64 = (base64String + padding)
    .replace(/\-/g, '+')
    .replace(/_/g, '/');

  const rawData = window.atob(base64);
  const outputArray = new Uint8Array(rawData.length);

  for (let i = 0; i < rawData.length; ++i) {
    outputArray[i] = rawData.charCodeAt(i);
  }
  return outputArray;
}

async function subscribeAdminToPush() {
  if (!('serviceWorker' in navigator) || !('PushManager' in window)) {
    return;
  }
  try {
    const reg = await navigator.serviceWorker.ready;
    let sub = await reg.pushManager.getSubscription();
    
    if (Notification.permission === 'default') {
      const permission = await Notification.requestPermission();
      if (permission !== 'granted') return;
    } else if (Notification.permission !== 'granted') {
      return;
    }

    if (!sub) {
      const applicationServerKey = urlB64ToUint8Array('BA98sR8lNAUUcfJ4JfLzqmpUEaDy4hLWfzoPCjtejclHgSxUCXxMoEXIMl4mWkH5ZoiWl7agdSsCKZ3DYXzKxgE');
      sub = await reg.pushManager.subscribe({
        userVisibleOnly: true,
        applicationServerKey
      });
    }

    await fetch('actions.php?action=saveAdminPushSubscription', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(sub)
    });
  } catch (err) {
    console.error('Push subscription failed:', err);
  }
}

/* ─ init ─ */
setView('overview', currentRange);
subscribeAdminToPush();
</script>
</body>
</html>