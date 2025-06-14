<?php
session_start();
include "class/class.php";

if (!isset($_SESSION['login_admin']) || $_SESSION['login_admin']['role'] !== 'MGR') {
    echo "Access denied. You must be a manager to access this page.";
    exit;
}

$database = new DataBase();
$koneksi = $database->koneksi;
$barang = new Barang($koneksi);

$kd_barang = $_GET['kd_barang'] ?? null;
if (!$kd_barang) {
    echo "No item specified.";
    exit;
}

$history = $barang->get_approval_history($kd_barang);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Approval History for <?php echo htmlspecialchars($kd_barang); ?></title>
    <link rel="stylesheet" href="assets/css/bootstrap.css">
</head>
<body>
<div class="container">
    <h2>Approval History for Item: <?php echo htmlspecialchars($kd_barang); ?></h2>
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Action</th>
                <th>Changed By</th>
                <th>Change Details</th>
                <th>Action Date</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($history)) { ?>
            <tr>
                <td colspan="4" class="text-center">No approval history found.</td>
            </tr>
            <?php } else { ?>
                <?php foreach ($history as $entry) { ?>
                <tr>
                    <td><?php echo htmlspecialchars($entry['action']); ?></td>
                    <td><?php echo htmlspecialchars($entry['changed_by_name']); ?></td>
                    <td><?php echo nl2br(htmlspecialchars($entry['change_details'])); ?></td>
                    <td><?php echo htmlspecialchars($entry['action_date']); ?></td>
                </tr>
                <?php } ?>
            <?php } ?>
        </tbody>
    </table>
    <a href="approval_barang.php" class="btn btn-primary">Back to Approval List</a>
</div>
</body>
</html>
