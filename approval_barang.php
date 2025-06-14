<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
include_once "class/class.php";

if (!isset($_SESSION['login_admin']) || $_SESSION['login_admin']['role'] !== 'MGR') {
    echo "Access denied. You must be a manager to access this page.";
    exit;
}

$database = new DataBase();
$koneksi = $database->koneksi;
$barang = new Barang($koneksi);

if (isset($_POST['approve'])) {
    $kd_barang_beli = $_POST['kd_barang_beli'];
    // Update status in barang_pembelian
    $stmt = $koneksi->prepare("UPDATE barang_pembelian SET status = 1 WHERE kd_barang_beli = ?");
    $stmt->bind_param("s", $kd_barang_beli);
    if (!$stmt->execute()) {
        $error_message = "Error updating status: " . $stmt->error;
    }
    $stmt->close();
    // Insert or update barang table with approved data
    $stmt2 = $koneksi->prepare("SELECT * FROM barang_pembelian WHERE kd_barang_beli = ?");
    $stmt2->bind_param("s", $kd_barang_beli);
    $stmt2->execute();
    $result2 = $stmt2->get_result()->fetch_assoc();
    $stmt2->close();
    if ($result2) {
        // Check if barang exists
        $stmt3 = $koneksi->prepare("SELECT * FROM barang WHERE kd_barang = ?");
        $stmt3->bind_param("s", $result2['kd_barang_beli']);
        $stmt3->execute();
        $barang_exist = $stmt3->get_result()->fetch_assoc();
        $stmt3->close();
        if ($barang_exist) {
            // Update barang
            $stmt4 = $koneksi->prepare("UPDATE barang SET nama_barang = ?, satuan = ?, harga_jual = ?, harga_beli = ?, stok = stok + ? , status = 1 WHERE kd_barang = ?");
            $stmt4->bind_param("ssddis", $result2['nama_barang_beli'], $result2['satuan'], $_POST['harga_jual'], $result2['harga_beli'], $result2['item'], $result2['kd_barang_beli']);
            $stmt4->execute();
            $stmt4->close();
        } else {
            // Insert new barang
            $stmt5 = $koneksi->prepare("INSERT INTO barang (kd_barang, nama_barang, satuan, harga_jual, harga_beli, stok, status) VALUES (?, ?, ?, ?, ?, ?, 1)");
            $stmt5->bind_param("sssddis", $result2['kd_barang_beli'], $result2['nama_barang_beli'], $result2['satuan'], $_POST['harga_jual'], $result2['harga_beli'], $result2['item']);
            $stmt5->execute();
            $stmt5->close();
        }
    }
    // Update pembelian status if all barang_pembelian approved or rejected
    $kd_pembelian = $result2['kd_pembelian'];
    $stmt6 = $koneksi->prepare("SELECT COUNT(*) AS pending_count FROM barang_pembelian WHERE kd_pembelian = ? AND status = 0");
    $stmt6->bind_param("s", $kd_pembelian);
    $stmt6->execute();
    $pending_count = $stmt6->get_result()->fetch_assoc()['pending_count'];
    $stmt6->close();

    $stmt7 = $koneksi->prepare("SELECT COUNT(*) AS rejected_count FROM barang_pembelian WHERE kd_pembelian = ? AND status = 2");
    $stmt7->bind_param("s", $kd_pembelian);
    $stmt7->execute();
    $rejected_count = $stmt7->get_result()->fetch_assoc()['rejected_count'];
    $stmt7->close();

    if ($pending_count == 0) {
        if ($rejected_count > 0) {
            $new_status = 'rejected';
        } else {
            $new_status = 'approved';
        }
        $stmt8 = $koneksi->prepare("UPDATE pembelian SET status = ? WHERE kd_pembelian = ?");
        $stmt8->bind_param("ss", $new_status, $kd_pembelian);
        $stmt8->execute();
        $stmt8->close();
    }

    $barang->add_approval_history($kd_barang_beli, 'approve', $_SESSION['login_admin']['id']);
}
if (isset($error_message)) {
    echo "<script>alert('".$error_message."');</script>";
}

if (isset($_POST['reject'])) {
    $kd_barang_beli = $_POST['kd_barang_beli'];
    // Update status in barang_pembelian to rejected (e.g., 2)
    $stmt = $koneksi->prepare("UPDATE barang_pembelian SET status = 2 WHERE kd_barang_beli = ?");
    $stmt->bind_param("s", $kd_barang_beli);
    $stmt->execute();
    $stmt->close();
    $barang->add_approval_history($kd_barang_beli, 'reject', $_SESSION['login_admin']['id']);
}

if (isset($_POST['request_change'])) {
    $kd_barang = $_POST['kd_barang'];
    $change_details = $_POST['change_details'] ?? '';
    $stmt = $koneksi->prepare("UPDATE barang SET status = 0 WHERE kd_barang = ?");
    $stmt->bind_param("s", $kd_barang);
    $stmt->execute();
    $stmt->close();
    $barang->add_approval_history($kd_barang, 'request_change', $_SESSION['login_admin']['id'], $change_details);
}

$result = $koneksi->query("SELECT * FROM barang_pembelian WHERE status = 0 ORDER BY nama_barang_beli ASC");
?>

<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
include_once "class/class.php";

if (!isset($_SESSION['login_admin']) || $_SESSION['login_admin']['role'] !== 'MGR') {
    echo "Access denied. You must be a manager to access this page.";
    exit;
}

$database = new DataBase();
$koneksi = $database->koneksi;
$barang = new Barang($koneksi);

if (isset($_POST['approve'])) {
    $kd_barang_beli = $_POST['kd_barang'];
    // Update status in barang_pembelian
    $stmt = $koneksi->prepare("UPDATE barang_pembelian SET status = 1 WHERE kd_barang_beli = ?");
    $stmt->bind_param("s", $kd_barang_beli);
    if (!$stmt->execute()) {
        echo "Error updating status: " . $stmt->error;
    }
    $stmt->close();
    // Insert or update barang table with approved data
    $stmt2 = $koneksi->prepare("SELECT * FROM barang_pembelian WHERE kd_barang_beli = ?");
    $stmt2->bind_param("s", $kd_barang_beli);
    $stmt2->execute();
    $result2 = $stmt2->get_result()->fetch_assoc();
    $stmt2->close();
    if ($result2) {
        // Check if barang exists
        $stmt3 = $koneksi->prepare("SELECT * FROM barang WHERE kd_barang = ?");
        $stmt3->bind_param("s", $result2['kd_barang_beli']);
        $stmt3->execute();
        $barang_exist = $stmt3->get_result()->fetch_assoc();
        $stmt3->close();
        if ($barang_exist) {
            // Update barang
            $stmt4 = $koneksi->prepare("UPDATE barang SET nama_barang = ?, satuan = ?, harga_jual = ?, harga_beli = ?, stok = stok + ? , status = 1 WHERE kd_barang = ?");
            $stmt4->bind_param("ssddis", $result2['nama_barang_beli'], $result2['satuan'], $_POST['harga_jual'], $result2['harga_beli'], $result2['item'], $result2['kd_barang_beli']);
            $stmt4->execute();
            $stmt4->close();
        } else {
            // Insert new barang
            $stmt5 = $koneksi->prepare("INSERT INTO barang (kd_barang, nama_barang, satuan, harga_jual, harga_beli, stok, status) VALUES (?, ?, ?, ?, ?, ?, 1)");
            $stmt5->bind_param("sssddis", $result2['kd_barang_beli'], $result2['nama_barang_beli'], $result2['satuan'], $_POST['harga_jual'], $result2['harga_beli'], $result2['item']);
            $stmt5->execute();
            $stmt5->close();
        }
    }
    $barang->add_approval_history($kd_barang_beli, 'approve', $_SESSION['login_admin']['id']);
}

if (isset($_POST['reject'])) {
    $kd_barang_beli = $_POST['kd_barang'];
    // Update status in barang_pembelian to rejected (e.g., 2)
    $stmt = $koneksi->prepare("UPDATE barang_pembelian SET status = 2 WHERE kd_barang_beli = ?");
    $stmt->bind_param("s", $kd_barang_beli);
    $stmt->execute();
    $stmt->close();
    $barang->add_approval_history($kd_barang_beli, 'reject', $_SESSION['login_admin']['id']);
}

if (isset($_POST['request_change'])) {
    $kd_barang_beli = $_POST['kd_barang_beli'];
    $change_details = $_POST['change_details'] ?? '';
    $stmt = $koneksi->prepare("UPDATE barang_pembelian SET status = 0 WHERE kd_barang_beli = ?");
    $stmt->bind_param("s", $kd_barang_beli);
    $stmt->execute();
    $stmt->close();
    $barang->add_approval_history($kd_barang_beli, 'request_change', $_SESSION['login_admin']['id'], $change_details);
}

$result = $koneksi->query("SELECT * FROM barang_pembelian WHERE status = 0 ORDER BY nama_barang_beli ASC");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Approval Barang</title>
    <link href="assets/css/bootstrap.css" rel="stylesheet" />
    <link href="assets/css/font-awesome.css" rel="stylesheet" />
    <link href="assets/css/custom.css" rel="stylesheet" />
</head>
<body>
  <div id="wrapper">
    <nav class="navbar navbar-default navbar-cls-top " role="navigation" style="margin-bottom: 0">
      <div class="navbar-header">
        <button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".sidebar-collapse">
          <span class="sr-only">Toggle navigation</span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>    
            <span class="icon-bar"></span>
        </button>
        <a class="navbar-brand" href="index.php">Cevrie Point Store</a> 
      </div>
      <div style="color: white; padding: 15px 20px 15px 20px; float: right;font-size: 16px;"> 
        <span style="margin-right:20px"><?php echo date('D, d F, Y'); ?></span>
        <a href="index.php?page=logout" class="btn btn-danger square-btn-adjust">Logout</a> 
      </div>
    </nav>   
<!-- /. NAV TOP  -->
    <nav class="navbar-default navbar-side" role="navigation">
      <div class="sidebar-collapse">
        <ul class="nav" id="main-menu">
          <li class="text-center">
            <img src="gambar_admin/<?php echo $_SESSION['login_admin']['gambar']; ?>" class="user-image img-circle img-responsive"/>
          </li>
          <?php if ($_SESSION['login_admin']['role'] === 'ADMIN') { ?>
          <li>
            <a  class="active-menu" href="index.php"><i class="fa fa-dashboard"></i> Dashboard</a>
          </li> 
          <li>
            <a  href="#"><i class="fa fa-money"></i> Pembelian<span class="fa arrow"></span></a>
            <ul class="nav nav-second-level">
              <li>
                <a  href="index.php?page=barangpembelian"><i class="fa fa-cube"></i> Data Barang Pembelian</a>
              </li>
              <li>
                <a  href="index.php?page=pembelian"><i class="fa fa-database"></i> Data Pembelian</a>
              </li>
              <li>
                <a  href="index.php?page=tambahpembelian"><i class="fa fa-plus-square"></i> Tambah Data</a>
              </li>
            </ul>
          </li>
          <li>
            <a  href="#"><i class="fa fa-money"></i> Penjualan<span class="fa arrow"></span></a>
            <ul class="nav nav-second-level">
              <li>
                <a  href="index.php?page=penjualan"><i class="fa fa-database"></i> Data Penjualan</a>
              </li>
              <li>
                <a  href="index.php?page=tambahpenjualan"><i class="fa fa-plus-square"></i> Tambah Data</a>
              </li>
            </ul>
          </li>
          <li>
            <a  href="index.php?page=barang"><i class="fa fa-qrcode"></i> Barang</a>
          </li>
          <li>
            <a  href="index.php?page=supplier"><i class="fa fa-group"></i> Supplier</a>
          </li>
          <li>
            <a  href="#"><i class="fa fa-book"></i> Laporan<span class="fa arrow"></span></a>
            <ul class="nav nav-second-level">
              <li>
                <a href="index.php?page=laporanpenjualan"><i class="fa fa-file-archive-o"></i> Penjualan</a>
              </li>
              <li>
                <a href="index.php?page=laporanpembelian"><i class="fa fa-file-archive-o"></i> Pembelian</a>
              </li>
              <li>
                <a href="index.php?page=laporanprofit"><i class="fa fa-dollar"></i> Profit</a>
              </li>
            </ul>
          </li>
          <li>
            <a  href="#"><i class="fa fa-wrench"></i> Pengaturan<span class="fa arrow"></span></a>
            <ul class="nav nav-second-level">
              <li>
                <a href="index.php?page=admin"><i class="fa fa-user"></i> Admin</a>
              </li>
              <li>
                <a href="index.php?page=perusahaan"><i class="fa fa-home"></i> Perusahaan</a>
              </li>
            </ul>
          </li>
          <?php } elseif ($_SESSION['login_admin']['role'] === 'MGR') { ?>
          <li>
            <a  class="active-menu" href="index.php"><i class="fa fa-dashboard"></i> Dashboard</a>
          </li>
          <li>
            <a href="approval_barang.php"><i class="fa fa-check-square"></i> Approval Barang</a>
          </li>
          <li>
            <a href="#"><i class="fa fa-book"></i> Laporan<span class="fa arrow"></span></a>
            <ul class="nav nav-second-level">
              <li>
                <a href="index.php?page=laporanpenjualan"><i class="fa fa-file-archive-o"></i> Penjualan</a>
              </li>
              <li>
                <a href="index.php?page=laporanpembelian"><i class="fa fa-file-archive-o"></i> Pembelian</a>
              </li>
              <li>
                <a href="index.php?page=laporanprofit"><i class="fa fa-dollar"></i> Profit</a>
              </li>
            </ul>
          </li>
          <li>
            <a href="index.php?page=barang"><i class="fa fa-qrcode"></i> Barang</a>
          </li>
          <li>
            <a href="index.php?page=supplier"><i class="fa fa-group"></i> Supplier</a>
          </li>
          <li>
            <a href="#"><i class="fa fa-wrench"></i> Pengaturan<span class="fa arrow"></span></a>
            <ul class="nav nav-second-level">
              <li>
                <a href="index.php?page=admin"><i class="fa fa-user"></i> Admin</a>
              </li>
              <li>
                <a href="index.php?page=perusahaan"><i class="fa fa-home"></i> Perusahaan</a>
              </li>
            </ul>
          </li>
          <?php } ?>
      </div>      
    </nav>  
<!-- /. NAV SIDE  -->
    <div id="page-wrapper">
      <div id="page-inner">
        <h1>Daftar Barang Pending Approval</h1>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Kode Barang</th>
                    <th>Nama Barang</th>
                    <th>Satuan</th>
                    <th>Harga Jual</th>
                    <th>Harga Beli</th>
                    <th>Stok</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()) { ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['kd_barang']); ?></td>
                    <td><?php echo htmlspecialchars($row['nama_barang']); ?></td>
                    <td><?php echo htmlspecialchars($row['satuan']); ?></td>
                    <td><?php echo htmlspecialchars($row['harga_jual']); ?></td>
                    <td><?php echo htmlspecialchars($row['harga_beli']); ?></td>
                    <td><?php echo htmlspecialchars($row['stok']); ?></td>
                    <td>
                        <form method="POST" id="approveForm<?php echo htmlspecialchars($row['kd_barang']); ?>" style="display:inline;" onsubmit="return confirmApprove('<?php echo htmlspecialchars($row['kd_barang']); ?>')">
                            <input type="hidden" name="kd_barang_beli" value="<?php echo htmlspecialchars($row['kd_barang_beli']); ?>">
                            <input type="hidden" name="change_details" id="change_details_approve_<?php echo htmlspecialchars($row['kd_barang_beli']); ?>" value="">
                            <div class="form-group" style="display:inline-block; width: 120px; margin-right: 5px;">
                                <input type="number" name="harga_jual" id="harga_jual_<?php echo htmlspecialchars($row['kd_barang_beli']); ?>" class="form-control input-sm" placeholder="Harga Jual" required min="0">
                            </div>
                            <button type="submit" name="approve" class="btn btn-success btn-sm">Approve</button>
                        </form>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="kd_barang_beli" value="<?php echo htmlspecialchars($row['kd_barang_beli']); ?>">
                            <button type="submit" name="reject" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure to reject this item?');">Reject</button>
                        </form>
                        <button class="btn btn-warning btn-sm" onclick="showRequestChangeForm('<?php echo htmlspecialchars($row['kd_barang_beli']); ?>')">Request Change</button>
                    </td>
                </tr>
                <?php } ?>
                <?php if ($result->num_rows === 0) { ?>
                <tr>
                    <td colspan="7" class="text-center">No pending items for approval.</td>
                </tr>
                <?php } ?>
            </tbody>
        </table>
      </div>
    </div>
  </div>
  <script>
  function showRequestChangeForm(kd_barang_beli) {
      var formHtml = `
          <form method="POST" onsubmit="return confirm('Submit request for change?');">
              <input type="hidden" name="kd_barang_beli" value="` + kd_barang_beli + `">
              <div class="form-group">
                  <label>Change Details</label>
                  <textarea name="change_details" class="form-control" rows="3" required></textarea>
              </div>
              <button type="submit" name="request_change" class="btn btn-warning btn-sm">Submit Request</button>
              <button type="button" class="btn btn-secondary btn-sm" onclick="closeRequestChangeForm()">Cancel</button>
          </form>
      `;
      var container = document.createElement('tr');
      container.id = 'requestChangeFormRow';
      container.innerHTML = '<td colspan="7">' + formHtml + '</td>';
      var tableBody = document.querySelector('table.table tbody');
      // Remove existing form if any
      var existingForm = document.getElementById('requestChangeFormRow');
      if (existingForm) {
          existingForm.remove();
      }
      // Insert form after the row of the clicked button
      var rows = tableBody.querySelectorAll('tr');
      for (var i = 0; i < rows.length; i++) {
          var row = rows[i];
          if (row.querySelector('input[name="kd_barang_beli"]').value === kd_barang_beli) {
              row.parentNode.insertBefore(container, row.nextSibling);
              break;
          }
      }
  }
  
  function closeRequestChangeForm() {
      var existingForm = document.getElementById('requestChangeFormRow');
      if (existingForm) {
          existingForm.remove();
      }
  }
  function confirmApprove(kd_barang_beli) {
      var hargaJual = document.getElementById('harga_jual_' + kd_barang_beli).value;
      if (!hargaJual || hargaJual <= 0) {
          alert('Please enter a valid selling price.');
          return false;
      }
      var suggestion = prompt('Please enter any suggestions or complaints for the admin (optional):');
      if (suggestion === null) {
          // User cancelled
          return false;
      }
      document.getElementById('change_details_approve_' + kd_barang_beli).value = suggestion;
      return true;
  }
  </script>
</body>
</html>
