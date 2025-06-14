<style type="text/css">
* {
	font-family: 'Segoe UI', Arial, sans-serif;
	font-size: 11pt;
	margin: 0;
	padding: 0;
	box-sizing: border-box;
}
@page {
	margin: 2cm;
}
.container {
	width: 100%;
	margin: auto;
}
.header {
	text-align: center;
	margin-bottom: 20px;
}
.header h1 {
	font-size: 18pt;
	margin-bottom: 0;
}
.header p {
	font-size: 11pt;
	margin-top: 5px;
}
.header h2 {
	margin-top: 20px;
	font-size: 14pt;
	text-decoration: underline;
}
table.grid {
	width: 100%;
	border-collapse: collapse;
	margin-top: 15px;
}
table.grid th, table.grid td {
	border: 1px solid #000;
	padding: 6px;
	text-align: left;
}
table.grid th {
	background-color: #f0f0f0;
	text-align: center;
}
.footer {
	margin-top: 30px;
	text-align: right;
}
.footer .ttd {
	margin-top: 90px;
}
.page-number {
	text-align: center;
	font-size: 10pt;
	margin-top: 20px;
}
.pagebreak {
	page-break-after: always;
}
</style>

<?php
error_reporting(0);
include '../class/class.php';

if (isset($_GET['tgl1']) && isset($_GET['tgl2'])) {
	$dat = $cetaklaporan->laporan_pembelian_bulan($_GET['tgl1'], $_GET['tgl2']);
} else {
	$dat = $cetaklaporan->laporan_semua_pembelian();
}

$per = $perusahaan->tampil_perusahaan();
$namaper = $per['nama_perusahaan'];
$alamat = $per['alamat'];
$pemilik = $per['pemilik'];
$kota = $per['kota'];
$judul_H = "LAPORAN PEMBELIAN BARANG";
$tgl = date('d-m-Y');

function header_laporan($judul_H, $namaper, $alamat) {
	echo "<div class='header'>
		<h1>$namaper</h1>
		<p>$alamat</p>
		<h2>$judul_H</h2>
	</div>
	<table class='grid'>
		<tr>
			<th width='3%'>No</th>
			<th width='12%'>Kode</th>
			<th width='10%'>Tanggal</th>
			<th width='15%'>Supplier</th>
			<th>Nama Barang</th>
			<th width='8%'>Satuan</th>
			<th width='8%'>Jumlah</th>
			<th width='12%'>Harga</th>
			<th width='12%'>Total</th>
		</tr>";
}

function footer_laporan() {
	echo "</table>";
}

echo "<div class='container'>";
$page = 1;
$gtotal = 0;

if (count($dat) === 0) {
	echo "<p><b>Tidak ada data pembelian untuk tanggal yang dipilih.</b></p>";
} else {
	foreach ($dat as $index => $data) {
		$no = $index + 1;
		$total = $data['harga_beli'] * $data['jumlah'];
		$gtotal += $total;

		if (($no % 25) == 1) {
			if ($index > 0) {
				footer_laporan();
				echo "<div class='page-number'>Hal - $page</div>
					<div class='pagebreak'></div>";
				$page++;
			}
			header_laporan($judul_H, $namaper, $alamat);
		}

		echo "<tr>
			<td align='center'>$no</td>
			<td align='center'>{$data['kd_pembelian']}</td>
			<td align='center'>" . date_format(date_create($data['tgl_pembelian']), 'd-m-Y') . "</td>
			<td>{$data['nama_supplier']}</td>
			<td>{$data['nama_barang_beli']}</td>
			<td align='center'>{$data['satuan']}</td>
			<td align='center'>{$data['jumlah']}</td>
			<td align='right'>Rp " . number_format($data['harga_beli'], 0, ',', '.') . "</td>
			<td align='right'>Rp " . number_format($total, 0, ',', '.') . "</td>
		</tr>";
	}

	echo "<tr>
		<td colspan='8' align='center'><b>Grand Total</b></td>
		<td align='right'><b>Rp " . number_format($gtotal, 0, ',', '.') . "</b></td>
	</tr>";
	footer_laporan();

	echo "<div class='footer'>
		<div>$kota, $tgl</div>
		<div class='ttd'>$pemilik</div>
	</div>";

	echo "<div class='page-number'>Hal - $page</div>";
}
echo "</div>";
?>

<script type="text/javascript">
	window.print();
</script>
