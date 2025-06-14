<?php
date_default_timezone_set('Asia/Jakarta');

class DataBase {
    private $host = "localhost";
    private $user = "root";
    private $pass = "";
    private $db = "inventory_barang";
    public $koneksi;
    
    public function __construct() {
        $this->koneksi = new mysqli($this->host, $this->user, $this->pass, $this->db);
        if ($this->koneksi->connect_error) {
            error_log("Connection failed: " . $this->koneksi->connect_error);
            die("Database connection failed. Please check your database server and credentials.");
        }
    }
}

class Admin {
    private $koneksi;
    
    public function __construct($koneksi) {
        $this->koneksi = $koneksi;
    }
    
    public function simpan_admin($email, $pass, $nama, $gambar) {
        $namafile = $gambar['name'];
        $lokasifile = $gambar['tmp_name'];
        move_uploaded_file($lokasifile, "gambar_admin/$namafile");
        
        $stmt = $this->koneksi->prepare("INSERT INTO admin(email, password, nama, gambar) VALUES(?, ?, ?, ?)");
        $stmt->bind_param("ssss", $email, $pass, $nama, $namafile);
        return $stmt->execute();
    }
    
    public function tampil_admin() {
        $result = $this->koneksi->query("SELECT * FROM admin");
        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
        return $data;
    }
    
    public function ambil_admin($id) {
        $stmt = $this->koneksi->prepare("SELECT * FROM admin WHERE kd_admin = ?");
        $stmt->bind_param("s", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }
    
    public function ubah_admin($email, $pass, $nama, $gambar, $id) {
        $ambil = $this->ambil_admin($id);
        $gambarhapus = $ambil['gambar'];
        
        if (!empty($gambar['tmp_name'])) {
            unlink("gambar_admin/$gambarhapus");
            $namafile = $gambar['name'];
            $lokasifile = $gambar['tmp_name'];
            move_uploaded_file($lokasifile, "gambar_admin/$namafile");
            
            $stmt = $this->koneksi->prepare("UPDATE admin SET email=?, password=?, nama=?, gambar=? WHERE kd_admin=?");
            $stmt->bind_param("sssss", $email, $pass, $nama, $namafile, $id);
        } else {
            $stmt = $this->koneksi->prepare("UPDATE admin SET email=?, password=?, nama=? WHERE kd_admin=?");
            $stmt->bind_param("ssss", $email, $pass, $nama, $id);
        }
        return $stmt->execute();
    }
    
    public function hapus_admin($hapus) {
        $gbr = $this->ambil_admin($hapus);
        $namagbr = $gbr['gambar'];
        unlink("gambar_admin/$namagbr");
        
        $stmt = $this->koneksi->prepare("DELETE FROM admin WHERE kd_admin=?");
        $stmt->bind_param("s", $hapus);
        return $stmt->execute();
    }
    
    public function login_admin($email, $pass) {
        $stmt = $this->koneksi->prepare("SELECT * FROM admin WHERE email=? AND password=?");
        $stmt->bind_param("ss", $email, $pass);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $data = $result->fetch_assoc();
            $_SESSION['login_admin'] = [
                'id' => $data['kd_admin'],
                'email' => $data['email'],
                'nama' => $data['nama'],
                'gambar' => $data['gambar'],
                'role' => $data['role'] ?? 'ADMIN'
            ];
            return true;
        }
        return false;
    }
}

class Barang {
    private $koneksi;
    
    public function __construct($koneksi) {
        $this->koneksi = $koneksi;
    }
    
    public function tampil_barang() {
        $result = $this->koneksi->query("SELECT * FROM barang ORDER BY nama_barang ASC");
        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
        return $data;
    }
    
    public function get_approval_status($kd_barang) {
        $stmt = $this->koneksi->prepare("SELECT status FROM barang WHERE kd_barang = ?");
        $stmt->bind_param("s", $kd_barang);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        if (!$result) {
            return "Unknown";
        }
        if ($result['status'] === '0') {
            return "Pending";
        } elseif ($result['status'] === '1') {
            return "Approved";
        } else {
            return "Unknown";
        }
    }
    
    public function add_approval_history($kd_barang, $action, $changed_by, $change_details = null) {
        $stmt = $this->koneksi->prepare("INSERT INTO approval_history (kd_barang, action, changed_by, change_details) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssis", $kd_barang, $action, $changed_by, $change_details);
        return $stmt->execute();
    }
    
    public function get_approval_history($kd_barang) {
        $stmt = $this->koneksi->prepare("SELECT ah.*, a.nama as changed_by_name FROM approval_history ah JOIN admin a ON ah.changed_by = a.kd_admin WHERE kd_barang = ? ORDER BY action_date DESC");
        $stmt->bind_param("s", $kd_barang);
        $stmt->execute();
        $result = $stmt->get_result();
        $history = [];
        while ($row = $result->fetch_assoc()) {
            $history[] = $row;
        }
        return $history;
    }
    
    public function simpan_barang($kdbarang, $nama, $satuan, $hargaj, $hargab, $stok) {
        $stmt = $this->koneksi->prepare("INSERT INTO barang(kd_barang, nama_barang, satuan, harga_jual, harga_beli, stok) VALUES(?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssddd", $kdbarang, $nama, $satuan, $hargaj, $hargab, $stok);
        return $stmt->execute();
    }
    
    public function ubah_barang($nama, $satuan, $hargaj, $hargab, $stok, $kd) {
        $stmt = $this->koneksi->prepare("UPDATE barang SET nama_barang=?, satuan=?, harga_jual=?, harga_beli=?, stok=? WHERE kd_barang=?");
        $stmt->bind_param("ssddds", $nama, $satuan, $hargaj, $hargab, $stok, $kd);
        return $stmt->execute();
    }
    
    public function ambil_barang($id) {
        $stmt = $this->koneksi->prepare("SELECT * FROM barang WHERE kd_barang=?");
        $stmt->bind_param("s", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }
    
    public function hapus_barang($kd) {
        $stmt = $this->koneksi->prepare("DELETE FROM barang WHERE kd_barang=?");
        $stmt->bind_param("s", $kd);
        return $stmt->execute();
    }
    
    public function simpan_barang_gudang($kdbarang, $hargaj, $kdbl) {
        $dat = $this->ambil_barangpem($kdbl);
        $stmt = $this->koneksi->prepare("INSERT INTO barang(kd_barang, nama_barang, satuan, harga_jual, harga_beli, stok) VALUES(?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssddd", $kdbarang, $dat['nama_barang_beli'], $dat['satuan'], $hargaj, $dat['harga_beli'], $dat['item']);
        $stmt->execute();
        
        $stmt = $this->koneksi->prepare("UPDATE barang_pembelian SET status='1' WHERE kd_barang_beli=?");
        $stmt->bind_param("s", $kdbl);
        return $stmt->execute();
    }
    
    public function ambil_barangpem($kd) {
        $stmt = $this->koneksi->prepare("SELECT * FROM barang_pembelian WHERE kd_barang_beli=?");
        $stmt->bind_param("s", $kd);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }
}

class Supplier {
    private $koneksi;
    
    public function __construct($koneksi) {
        $this->koneksi = $koneksi;
    }
    
    public function tampil_supplier() {
        $result = $this->koneksi->query("SELECT * FROM supplier");
        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
        return $data;
    }
    
    public function simpan_supplier($nama, $alamat) {
        $stmt = $this->koneksi->prepare("INSERT INTO supplier(nama_supplier, alamat) VALUES(?, ?)");
        $stmt->bind_param("ss", $nama, $alamat);
        return $stmt->execute();
    }
    
    public function ubah_supplier($nama, $alamat, $id) {
        $stmt = $this->koneksi->prepare("UPDATE supplier SET nama_supplier=?, alamat=? WHERE kd_supplier=?");
        $stmt->bind_param("sss", $nama, $alamat, $id);
        return $stmt->execute();
    }
    
    public function hapus_supplier($id) {
        $stmt = $this->koneksi->prepare("DELETE FROM supplier WHERE kd_supplier=?");
        $stmt->bind_param("s", $id);
        return $stmt->execute();
    }
    
    public function ambil_supplier($id) {
        $stmt = $this->koneksi->prepare("SELECT * FROM supplier WHERE kd_supplier=?");
        $stmt->bind_param("s", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }
}

class Pembelian {
    private $koneksi;
    
    public function __construct($koneksi) {
        $this->koneksi = $koneksi;
    }
    
    public function kode_otomatis() {
        $result = $this->koneksi->query("SELECT MAX(kd_pembelian) AS kode FROM pembelian");
        $pecah = $result->fetch_assoc();
        $kode = substr($pecah['kode'], 3, 5);
        $jum = $kode + 1;
        
        if ($jum < 10) return "PEM0000".$jum;
        elseif ($jum < 100) return "PEM000".$jum;
        elseif ($jum < 1000) return "PEM00".$jum;
        else return "PEM0".$jum;
    }
    
    public function tampil_pembelian() {
        $result = $this->koneksi->query("SELECT * FROM pembelian p JOIN supplier s ON p.kd_supplier=s.kd_supplier ORDER BY kd_pembelian DESC");
        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
        return $data;
    }
    
    public function get_approval_status_by_pembelian($kd_pembelian) {
        // Get all barang_pembelian related to this pembelian
        $stmt = $this->koneksi->prepare("SELECT bp.status FROM barang_pembelian bp JOIN d_pembelian dp ON bp.kd_barang_beli = dp.kd_barang_beli WHERE dp.kd_pembelian = ?");
        $stmt->bind_param("s", $kd_pembelian);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $statuses = [];
        while ($row = $result->fetch_assoc()) {
            $statuses[] = $row['status'];
        }
        $stmt->close();
        
        if (empty($statuses)) {
            return "Unknown";
        }
        if (in_array('0', $statuses)) {
            return "Pending";
        }
        if (in_array('1', $statuses)) {
            return "Approved";
        }
        return "Unknown";
    }
    
    public function hitung_item_pembelian($kdpembelian) {
        $stmt = $this->koneksi->prepare("SELECT count(*) as jumlah FROM d_pembelian WHERE kd_pembelian=?");
        $stmt->bind_param("s", $kdpembelian);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }
    
    public function tambah_barang_sementara($kode, $nama, $satuan, $hargab, $item) {
        $tot = $item * $hargab;
        $stmt = $this->koneksi->prepare("INSERT INTO barangp_sementara(kd_pembelian, nama_barangp, satuan, harga_barangp, item, total) VALUES(?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssdds", $kode, $nama, $satuan, $hargab, $item, $tot);
        return $stmt->execute();
    }
    
    public function tampil_barang_sementara($kode) {
        $stmt = $this->koneksi->prepare("SELECT * FROM barangp_sementara WHERE kd_pembelian=?");
        $stmt->bind_param("s", $kode);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
        return $data;
    }
    
    public function hitung_total_sementara($kode) {
        $stmt = $this->koneksi->prepare("SELECT sum(total) as jumlah FROM barangp_sementara WHERE kd_pembelian=?");
        $stmt->bind_param("s", $kode);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        return $result['jumlah'] ?? 0;
    }
    
    public function hapus_barang_sementara($hapus) {
        $stmt = $this->koneksi->prepare("DELETE FROM barangp_sementara WHERE id_barangp=?");
        $stmt->bind_param("s", $hapus);
        return $stmt->execute();
    }
    
    public function cek_data_barangp($kode) {
        $stmt = $this->koneksi->prepare("SELECT * FROM barangp_sementara WHERE kd_pembelian=?");
        $stmt->bind_param("s", $kode);
        $stmt->execute();
        return $stmt->get_result()->num_rows >= 1;
    }
    
    public function simpan_pembelian($kdpembelian, $tglpembelian, $supplier, $totalpem) {
        $kdadmin = $_SESSION['login_admin']['id'];
        
        // Mulai transaksi
        $this->koneksi->begin_transaction();
        
        try {
            // Insert pembelian
            $stmt = $this->koneksi->prepare("INSERT INTO pembelian(kd_pembelian, tgl_pembelian, kd_admin, kd_supplier, total_pembelian) VALUES(?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssd", $kdpembelian, $tglpembelian, $kdadmin, $supplier, $totalpem);
            $stmt->execute();
            
            // Insert data barang pembelian
            $this->koneksi->query("INSERT INTO barang_pembelian(kd_pembelian, nama_barang_beli, satuan, harga_beli, item, total) 
                                  SELECT kd_pembelian, nama_barangp, satuan, harga_barangp, item, total FROM barangp_sementara");
            
            // Insert detail pembelian
            $this->koneksi->query("INSERT INTO d_pembelian(kd_pembelian, kd_barang_beli, jumlah, subtotal) 
                                  SELECT kd_pembelian, kd_barang_beli, item, total FROM barang_pembelian WHERE kd_pembelian='$kdpembelian'");
            
            // Hapus data sementara
            $this->koneksi->query("DELETE FROM barangp_sementara WHERE kd_pembelian='$kdpembelian'");
            
            // Commit transaksi
            $this->koneksi->commit();
            return true;
        } catch (Exception $e) {
            $this->koneksi->rollback();
            return false;
        }
    }
    
    public function tampil_barang_pembelian() {
        $result = $this->koneksi->query("SELECT * FROM barang_pembelian WHERE status = '0'");
        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
        return $data;
    }
    
    public function ambil_kdpem() {
        $result = $this->koneksi->query("SELECT * FROM pembelian ORDER BY kd_pembelian DESC LIMIT 1");
        return $result->fetch_assoc();
    }
    
    public function cek_hapuspembelian($kd) {
        $stmt = $this->koneksi->prepare("SELECT * FROM barang_pembelian WHERE kd_pembelian=? AND status='0'");
        $stmt->bind_param("s", $kd);
        $stmt->execute();
        return $stmt->get_result()->num_rows == 0;
    }
    
    public function hitung_jumlah_pembelian($kd) {
        $stmt = $this->koneksi->prepare("SELECT SUM(subtotal) as total FROM d_pembelian WHERE kd_pembelian=?");
        $stmt->bind_param("s", $kd);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        return $result['total'];
    }
    
    public function hapus_pembelian($kdpembelian) {
        $this->koneksi->query("DELETE FROM pembelian WHERE kd_pembelian='$kdpembelian'");
        $this->koneksi->query("DELETE FROM barang_pembelian WHERE kd_pembelian='$kdpembelian' AND status='1'");
        return true;
    }
}

class Penjualan extends Barang {
    private $koneksi;
    
    public function __construct($koneksi) {
        parent::__construct($koneksi);
        $this->koneksi = $koneksi;
    }
    
    public function kode_otomatis() {
        $result = $this->koneksi->query("SELECT MAX(kd_penjualan) AS kode FROM penjualan");
        $pecah = $result->fetch_assoc();
        $kode = substr($pecah['kode'], 3, 5);
        $jum = $kode + 1;
        
        if ($jum < 10) return "PEN0000".$jum;
        elseif ($jum < 100) return "PEN000".$jum;
        elseif ($jum < 1000) return "PEN00".$jum;
        else return "PEN0".$jum;
    }
    
    public function tampil_barang_penjualan() {
        $result = $this->koneksi->query("SELECT * FROM barang WHERE stok > 0 ORDER BY nama_barang ASC");
        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
        return $data;
    }
    
    public function tampil_penjualan() {
        $result = $this->koneksi->query("SELECT * FROM penjualan ORDER BY kd_penjualan DESC");
        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
        return $data;
    }
    
    public function cek_data_barangp($kode) {
        $stmt = $this->koneksi->prepare("SELECT * FROM penjualan_sementara WHERE kd_penjualan=?");
        $stmt->bind_param("s", $kode);
        $stmt->execute();
        return $stmt->get_result()->num_rows >= 1;
    }
    
    public function tampil_barang_sementara($kode) {
        $stmt = $this->koneksi->prepare("SELECT * FROM penjualan_sementara WHERE kd_penjualan=?");
        $stmt->bind_param("s", $kode);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
        return $data;
    }
    
    public function tambah_penjualan_sementara($kdpen, $kdbarang, $item) {
        $bar = $this->ambil_barang($kdbarang);
        $namabr = $bar['nama_barang'];
        $satuan = $bar['satuan'];
        $harga = $bar['harga_jual'];
        $total = $harga * $item;
        
        $stmt = $this->koneksi->prepare("INSERT INTO penjualan_sementara(kd_penjualan, kd_barang, nama_barang, satuan, harga, item, total) VALUES(?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssddd", $kdpen, $kdbarang, $namabr, $satuan, $harga, $item, $total);
        $stmt->execute();
        
        // Update stok barang
        $kurang = $bar['stok'] - $item;
        $stmt = $this->koneksi->prepare("UPDATE barang SET stok=? WHERE kd_barang=?");
        $stmt->bind_param("ds", $kurang, $kdbarang);
        return $stmt->execute();
    }
    
    public function cek_item($kdbarang, $item) {
        $data = $this->ambil_barang($kdbarang);
        return $item < $data['stok'] + 1;
    }
    
    public function hitung_total_sementara($kode) {
        $stmt = $this->koneksi->prepare("SELECT sum(total) as jumlah FROM penjualan_sementara WHERE kd_penjualan=?");
        $stmt->bind_param("s", $kode);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        return $result['jumlah'] ?? 0;
    }
    
    public function hitung_item_penjualan($kdpenjualan) {
        $stmt = $this->koneksi->prepare("SELECT count(*) as jumlah FROM d_penjualan WHERE kd_penjualan=?");
        $stmt->bind_param("s", $kdpenjualan);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }
    
    public function simpan_penjualan($kdpenjualan, $tglpen, $ttlbayar, $subtotal) {
        $kdadmin = $_SESSION['login_admin']['id'];
        
        $this->koneksi->begin_transaction();
        
        try {
            // Insert penjualan
            $stmt = $this->koneksi->prepare("INSERT INTO penjualan(kd_penjualan, tgl_penjualan, kd_admin, dibayar, total_penjualan) VALUES(?, ?, ?, ?, ?)");
            $stmt->bind_param("sssdd", $kdpenjualan, $tglpen, $kdadmin, $ttlbayar, $subtotal);
            $stmt->execute();
            
            // Insert detail penjualan
            $this->koneksi->query("INSERT INTO d_penjualan(kd_penjualan, kd_barang, jumlah, subtotal) 
                                 SELECT kd_penjualan, kd_barang, item, total FROM penjualan_sementara WHERE kd_penjualan='$kdpenjualan'");
            
            // Hapus data sementara
            $this->koneksi->query("DELETE FROM penjualan_sementara WHERE kd_penjualan='$kdpenjualan'");
            
            $this->koneksi->commit();
            return true;
        } catch (Exception $e) {
            $this->koneksi->rollback();
            return false;
        }
    }
    
    public function ambil_kdpen() {
        $result = $this->koneksi->query("SELECT * FROM penjualan ORDER BY kd_penjualan DESC LIMIT 1");
        return $result->fetch_assoc();
    }
    
    public function hapus_penjualan_sementara($kd) {
        $datpen = $this->ambil_penjualan_sementara($kd);
        $datbar = $this->ambil_barang($datpen['kd_barang']);
        $stok = $datbar['stok'] + $datpen['item'];
        
        $this->koneksi->begin_transaction();
        
        try {
            $stmt = $this->koneksi->prepare("UPDATE barang SET stok=? WHERE kd_barang=?");
            $stmt->bind_param("ds", $stok, $datpen['kd_barang']);
            $stmt->execute();
            
            $stmt = $this->koneksi->prepare("DELETE FROM penjualan_sementara WHERE id_penjualan_sementara=?");
            $stmt->bind_param("s", $kd);
            $stmt->execute();
            
            $this->koneksi->commit();
            return true;
        } catch (Exception $e) {
            $this->koneksi->rollback();
            return false;
        }
    }
    
    public function ambil_penjualan_sementara($kd) {
        $stmt = $this->koneksi->prepare("SELECT * FROM penjualan_sementara WHERE id_penjualan_sementara=?");
        $stmt->bind_param("s", $kd);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }
}

class Nota {
    private $koneksi;
    
    public function __construct($koneksi) {
        $this->koneksi = $koneksi;
    }
    
    public function tampil_nota_pembelian($kd) {
        $stmt = $this->koneksi->prepare("SELECT * FROM supplier sup 
            JOIN pembelian pem ON pem.kd_supplier = sup.kd_supplier
            JOIN admin adm ON adm.kd_admin = pem.kd_admin
            JOIN d_pembelian dpem ON pem.kd_pembelian = dpem.kd_pembelian
            JOIN barang_pembelian bpem ON dpem.kd_barang_beli = bpem.kd_barang_beli
            WHERE pem.kd_pembelian = ?");
        $stmt->bind_param("s", $kd);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
        return $data;
    }
    
    public function ambil_nota_pembelian($kd) {
        $stmt = $this->koneksi->prepare("SELECT * FROM supplier sup 
            JOIN pembelian pem ON pem.kd_supplier = sup.kd_supplier
            JOIN admin adm ON adm.kd_admin = pem.kd_admin
            JOIN d_pembelian dpem ON pem.kd_pembelian = dpem.kd_pembelian
            JOIN barang_pembelian bpem ON dpem.kd_pembelian = bpem.kd_pembelian
            WHERE pem.kd_pembelian = ?");
        $stmt->bind_param("s", $kd);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }
    
    public function tampil_nota_penjualan($kd) {
        $stmt = $this->koneksi->prepare("SELECT * FROM penjualan pen
            JOIN admin adm ON adm.kd_admin = pen.kd_admin
            JOIN d_penjualan dpen ON pen.kd_penjualan = dpen.kd_penjualan
            JOIN barang bar ON dpen.kd_barang = bar.kd_barang
            WHERE pen.kd_penjualan = ?");
        $stmt->bind_param("s", $kd);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
        return $data;
    }
    
    public function ambil_nota_penjualan($kd) {
        $stmt = $this->koneksi->prepare("SELECT * FROM penjualan pen
            JOIN admin adm ON adm.kd_admin = pen.kd_admin
            JOIN d_penjualan dpen ON pen.kd_penjualan = dpen.kd_penjualan
            JOIN barang bar ON dpen.kd_barang = bar.kd_barang
            WHERE pen.kd_penjualan = ?");
        $stmt->bind_param("s", $kd);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }
}

class Laporan {
    private $koneksi;
    
    public function __construct($koneksi) {
        $this->koneksi = $koneksi;
    }
    
    public function tampil_penjualan_bulan($bln1, $bln2) {
        $stmt = $this->koneksi->prepare("SELECT * FROM penjualan pen
            JOIN d_penjualan dpen ON pen.kd_penjualan = dpen.kd_penjualan
            JOIN barang bar ON dpen.kd_barang = bar.kd_barang 
            WHERE pen.tgl_penjualan BETWEEN ? AND ?");
        $stmt->bind_param("ss", $bln1, $bln2);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
        return $data;
    }
    
    public function cek_penjualan_bulan($bln1, $bln2) {
        $stmt = $this->koneksi->prepare("SELECT * FROM penjualan pen
            JOIN d_penjualan dpen ON pen.kd_penjualan = dpen.kd_penjualan
            JOIN barang bar ON dpen.kd_barang = bar.kd_barang
            WHERE pen.tgl_penjualan BETWEEN ? AND ?");
        $stmt->bind_param("ss", $bln1, $bln2);
        $stmt->execute();
        return $stmt->get_result()->num_rows >= 1;
    }
    
    public function hitung_total_penjualan() {
        $result = $this->koneksi->query("SELECT sum(dpen.subtotal) as jumlah FROM penjualan pen
            JOIN d_penjualan dpen ON pen.kd_penjualan = dpen.kd_penjualan
            JOIN barang bar ON dpen.kd_barang = bar.kd_barang");
        return $result->fetch_assoc()['jumlah'];
    }
    
    public function tampil_penjualan() {
        $result = $this->koneksi->query("SELECT * FROM penjualan pen
            JOIN d_penjualan dpen ON pen.kd_penjualan = dpen.kd_penjualan
            JOIN barang bar ON dpen.kd_barang = bar.kd_barang");
        
        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
        return $data;
    }
    
    public function cek_penjualan() {
        $result = $this->koneksi->query("SELECT * FROM penjualan pen
            JOIN d_penjualan dpen ON pen.kd_penjualan = dpen.kd_penjualan
            JOIN barang bar ON dpen.kd_barang = bar.kd_barang");
        return $result->num_rows >= 1;
    }
    
    public function hitung_total_penjualan_bulan($bln1, $bln2) {
        $stmt = $this->koneksi->prepare("SELECT sum(dpen.subtotal) as jumlah FROM penjualan pen
            JOIN d_penjualan dpen ON pen.kd_penjualan = dpen.kd_penjualan
            JOIN barang bar ON dpen.kd_barang = bar.kd_barang
            WHERE pen.tgl_penjualan BETWEEN ? AND ?");
        $stmt->bind_param("ss", $bln1, $bln2);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc()['jumlah'];
    }
    
    public function tampil_pembelian_bulan($bln1, $bln2) {
        $stmt = $this->koneksi->prepare("SELECT * FROM supplier sup
            JOIN pembelian pem ON sup.kd_supplier = pem.kd_supplier
            JOIN d_pembelian dpem ON pem.kd_pembelian = dpem.kd_pembelian
            JOIN barang_pembelian barpem ON dpem.kd_barang_beli = barpem.kd_barang_beli 
            WHERE pem.tgl_pembelian BETWEEN ? AND ?");
        $stmt->bind_param("ss", $bln1, $bln2);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
        return $data;
    }
    
    public function cek_pembelian_bulan($bln1, $bln2) {
        $stmt = $this->koneksi->prepare("SELECT * FROM supplier sup
            JOIN pembelian pem ON sup.kd_supplier = pem.kd_supplier
            JOIN d_pembelian dpem ON pem.kd_pembelian = dpem.kd_pembelian
            JOIN barang_pembelian barpem ON dpem.kd_barang_beli = barpem.kd_barang_beli 
            WHERE pem.tgl_pembelian BETWEEN ? AND ?");
        $stmt->bind_param("ss", $bln1, $bln2);
        $stmt->execute();
        return $stmt->get_result()->num_rows >= 1;
    }
    
    public function hitung_total_pembelian_bulan($bln1, $bln2) {
        $stmt = $this->koneksi->prepare("SELECT sum(dpem.subtotal) as jumlah FROM supplier sup
            JOIN pembelian pem ON sup.kd_supplier = pem.kd_supplier
            JOIN d_pembelian dpem ON pem.kd_pembelian = dpem.kd_pembelian
            JOIN barang_pembelian barpem ON dpem.kd_barang_beli = barpem.kd_barang_beli 
            WHERE pem.tgl_pembelian BETWEEN ? AND ?");
        $stmt->bind_param("ss", $bln1, $bln2);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc()['jumlah'];
    }
    
    public function hitung_total_pembelian() {
        $result = $this->koneksi->query("SELECT sum(dpem.subtotal) as jumlah FROM supplier sup
            JOIN pembelian pem ON sup.kd_supplier = pem.kd_supplier
            JOIN d_pembelian dpem ON pem.kd_pembelian = dpem.kd_pembelian
            JOIN barang_pembelian barpem ON dpem.kd_barang_beli = barpem.kd_barang_beli");
        return $result->fetch_assoc()['jumlah'];
    }
    
    public function tampil_pembelian() {
        $result = $this->koneksi->query("SELECT * FROM supplier sup
            JOIN pembelian pem ON sup.kd_supplier = pem.kd_supplier
            JOIN d_pembelian dpem ON pem.kd_pembelian = dpem.kd_pembelian
            JOIN barang_pembelian barpem ON dpem.kd_barang_beli = barpem.kd_barang_beli");
        
        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
        return $data;
    }
    
    public function cek_pembelian() {
        $result = $this->koneksi->query("SELECT * FROM supplier sup
            JOIN pembelian pem ON sup.kd_supplier = pem.kd_supplier
            JOIN d_pembelian dpem ON pem.kd_pembelian = dpem.kd_pembelian
            JOIN barang_pembelian barpem ON dpem.kd_barang_beli = barpem.kd_barang_beli");
        return $result->num_rows >= 1;
    }
}

class Cetak_Laporan {
    private $koneksi;
    
    public function __construct($koneksi) {
        $this->koneksi = $koneksi;
    }
    
    public function laporan_penjualan_bulan($bln1, $bln2) {
        $stmt = $this->koneksi->prepare("SELECT * FROM penjualan pen
            JOIN d_penjualan dpen ON pen.kd_penjualan = dpen.kd_penjualan
            JOIN barang bar ON dpen.kd_barang = bar.kd_barang 
            WHERE pen.tgl_penjualan BETWEEN ? AND ?");
        $stmt->bind_param("ss", $bln1, $bln2);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
        return !empty($data) ? $data : null;
    }
    
    public function laporan_semua_penjualan() {
        $result = $this->koneksi->query("SELECT * FROM penjualan pen
            JOIN d_penjualan dpen ON pen.kd_penjualan = dpen.kd_penjualan
            JOIN barang bar ON dpen.kd_barang = bar.kd_barang");
        
        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
        return !empty($data) ? $data : null;
    }
    
    public function laporan_pembelian_bulan($bln1, $bln2) {
        $stmt = $this->koneksi->prepare("SELECT * FROM supplier sup
            JOIN pembelian pem ON sup.kd_supplier = pem.kd_supplier
            JOIN d_pembelian dpem ON pem.kd_pembelian = dpem.kd_pembelian
            JOIN barang_pembelian barpem ON dpem.kd_barang_beli = barpem.kd_barang_beli 
            WHERE pem.tgl_pembelian BETWEEN ? AND ?");
        $stmt->bind_param("ss", $bln1, $bln2);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
        return !empty($data) ? $data : null;
    }
    
    public function laporan_semua_pembelian() {
        $result = $this->koneksi->query("SELECT * FROM supplier sup
            JOIN pembelian pem ON sup.kd_supplier = pem.kd_supplier
            JOIN d_pembelian dpem ON pem.kd_pembelian = dpem.kd_pembelian
            JOIN barang_pembelian barpem ON dpem.kd_barang_beli = barpem.kd_barang_beli");
        
        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
        return !empty($data) ? $data : null;
    }
}

class Perusahaan {
    private $koneksi;
    
    public function __construct($koneksi) {
        $this->koneksi = $koneksi;
    }
    
    public function tampil_perusahaan() {
        $result = $this->koneksi->query("SELECT * FROM perusahaan WHERE kd_perusahaan = '1'");
        return $result->fetch_assoc();
    }
    
    public function simpan_perusahaan($nama, $alamat, $pemilik, $kota) {
        $stmt = $this->koneksi->prepare("UPDATE perusahaan SET nama_perusahaan=?, alamat=?, pemilik=?, kota=? WHERE kd_perusahaan='1'");
        $stmt->bind_param("ssss", $nama, $alamat, $pemilik, $kota);
        return $stmt->execute();
    }
}

class Dashboard {
    private $koneksi;
    
    public function __construct($koneksi) {
        $this->koneksi = $koneksi;
    }
    
    public function penjualan_hariini() {
        $hari = date("Y-m-d");
        $stmt = $this->koneksi->prepare("SELECT COUNT(*) as total FROM penjualan WHERE tgl_penjualan = ?");
        $stmt->bind_param("s", $hari);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc()['total'];
    }
    
    public function pembelian_hariini() {
        $hari = date("Y-m-d");
        $stmt = $this->koneksi->prepare("SELECT COUNT(*) as total FROM pembelian WHERE tgl_pembelian = ?");
        $stmt->bind_param("s", $hari);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc()['total'];
    }
}

// Initialize database connection
$database = new DataBase();
$koneksi = $database->koneksi;

// Initialize all classes with the connection
$admin = new Admin($koneksi);
$barang = new Barang($koneksi);
$supplier = new Supplier($koneksi);
$pembelian = new Pembelian($koneksi);
$penjualan = new Penjualan($koneksi);
$nota = new Nota($koneksi);
$laporan = new Laporan($koneksi);
$cetaklaporan = new Cetak_Laporan($koneksi);
$perusahaan = new Perusahaan($koneksi);
$dashboard = new Dashboard($koneksi);