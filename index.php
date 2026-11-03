<?php
session_start();
$_SESSION['computers'] = $_SESSION['computers'] ?? [];
$_SESSION['printers'] = $_SESSION['printers'] ?? [];
$computerCount = count($_SESSION['computers']);
$printerCount = count($_SESSION['printers']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard - Inventory System</title>
<link rel="stylesheet" href="index.css">
</head>
<body>
<aside class="sidebar">
  <div class="brand">Inventory System</div>
  <div class="section-label">Main Menu</div>
  <nav><a class="nav-link active" href="index.php"><span>▦</span>Dashboard</a>
<a class="nav-link" href="record.php"><span>▤</span>Inventory Record</a>

</nav>
</aside>
<main class="main">

<header class="header"><h1>Dashboard</h1><a class="button" href="addComputer.php">＋ Add Computer</a></header>
<p class="muted">Overview of your computer and printer inventory.</p>
<div class="stats">
  <section class="stat"><span class="muted">Computer Records</span><strong><?= $computerCount ?></strong><a href="record.php">View computer records →</a></section>
  <section class="stat"><span class="muted">Printer Records</span><strong><?= $printerCount ?></strong><a href="record.php#printer-list">View printer records →</a></section>
  <section class="stat"><span class="muted">Total Inventory</span><strong><?= $computerCount + $printerCount ?></strong><span class="muted">Computers + printers</span></section>
</div>

</main>
</body>
</html>
