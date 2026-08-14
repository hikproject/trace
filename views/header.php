<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Oracle Report - Leoco Production</title>
    <link href="/assets/vendor/bootstrap/bootstrap.min.css" rel="stylesheet">
    <link href="/assets/vendor/select2/select2.min.css" rel="stylesheet">
    <link href="/assets/vendor/select2/select2-bootstrap-5-theme.min.css" rel="stylesheet">
    <link href="/assets/style.css" rel="stylesheet">
</head>
<body>
    <div class="container-fluid py-4">
        <h3 class="mb-3">Oracle Production Report</h3>
        <?php $activePath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH); ?>
        <ul class="nav nav-tabs mb-3">
            <li class="nav-item">
                <a class="nav-link <?= $activePath === '/trace' ? '' : 'active' ?>" href="/">Report Produksi</a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $activePath === '/trace' ? 'active' : '' ?>" href="/trace">Trace Pengiriman</a>
            </li>
        </ul>
