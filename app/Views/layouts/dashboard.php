<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'Dashboard') ?> |  Inventory Management System</title>
    <link rel="stylesheet" href="/assets/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="/assets/bootstrap-icons/bootstrap-icons.min.css">
    <link rel="stylesheet" href="/assets/datatables/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="/assets/datatables/css/buttons.bootstrap5.min.css">
    <link rel="stylesheet" href="/assets/css/dashboard.css">
  
   
</head>
<body class="d-flex flex-column min-vh-100">

<?php require __DIR__ . '/../partials/header.php'; ?>

<div class="d-flex flex-grow-1">
    <?php require __DIR__ . '/../partials/sidebar.php'; ?>

    <main class="app-main flex-grow-1">
        <?php require __DIR__ . '/../partials/breadcrumb.php'; ?>
        <?= $content ?? '' ?>
    </main>
</div>
<?php require __DIR__ . '/../components/toast.php'; ?>
<?php require __DIR__ . '/../partials/footer.php'; ?>

<script src="/assets/bootstrap/js/bootstrap.bundle.min.js"></script>

<script src="/assets/datatables/js/dataTables.min.js"></script>
<script src="/assets/datatables/js/dataTables.bootstrap5.min.js"></script>

<script src="/assets/datatables/js/jszip.min.js"></script>

<script src="/assets/datatables/js/dataTables.buttons.min.js"></script>
<script src="/assets/datatables/js/buttons.bootstrap5.min.js"></script>
<script src="/assets/datatables/js/buttons.html5.min.js"></script>
<script src="/assets/datatables/js/buttons.print.min.js"></script>

<script src="/assets/js/toast.js"></script>
<script src="/assets/js/businesses.js"></script>
</body>
</html>