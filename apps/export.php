<?php
// Sertakan pustaka FPDF
require(__DIR__ . '/fpdf/fpdf.php'); // Menggunakan __DIR__ untuk jalur absolut

// Sertakan file konfigurasi
require_once "config.php";

$type = $_GET['type'];

if ($type == 'pdf') {
    class PDF extends FPDF
    {
        // Header halaman
        function Header()
        {
            // Arial bold 15
            $this->SetFont('Arial', 'B', 15);
            // Judul
            $this->Cell(0, 10, 'REKAP ABSENSI', 0, 1, 'C');
            // Garis baru
            $this->Ln(10);
        }

        // Footer halaman
        function Footer()
        {
            // Posisi 1.5 cm dari bawah
            $this->SetY(-15);
            // Arial italic 8
            $this->SetFont('Arial', 'I', 8);
            // Nomor halaman
            $this->Cell(0, 10, 'Halaman ' . $this->PageNo(), 0, 0, 'C');
        }

        // Muat data
        function LoadData()
        {
            global $link;
            $data = [];
            $sql = "SELECT data_absen.uid, DATE_FORMAT(tanggal, '%d-%m-%Y') AS tanggal, nama, division,
                    MIN(CASE WHEN status='IN' THEN waktu END) jam_masuk,
                    MAX(CASE WHEN status='OUT' THEN waktu END) jam_keluar
                    FROM data_absen, data_pengurus
                    WHERE data_absen.uid = data_pengurus.uid 
                    GROUP BY data_absen.uid, tanggal, nama, division";

            $result = mysqli_query($link, $sql);
            while ($row = mysqli_fetch_assoc($result)) {
                $data[] = $row;
            }
            return $data;
        }

        // Tabel
        function BasicTable($header, $data)
        {
            // Set lebar kolom
            $widths = array(35, 30, 50, 30, 30);
            // Set alignment kolom
            $aligns = array('C', 'C', 'L', 'C', 'C');
            
            // Header
            for ($i = 0; $i < count($header); $i++) {
                $this->Cell($widths[$i], 7, $header[$i], 1, 0, 'C');
            }
            $this->Ln();
            // Data
            foreach ($data as $row) {
                $x = $this->GetX();
                $y = $this->GetY();

                $this->Cell($widths[0], 6, $row['tanggal'], 1, 0, $aligns[0]);
                $this->Cell($widths[1], 6, $row['uid'], 1, 0, $aligns[1]);
                
                // Menggunakan MultiCell untuk kolom nama
                $this->SetXY($x + $widths[0] + $widths[1], $y);
                $this->MultiCell($widths[2], 6, $row['nama'], 1, $aligns[2]);

                // Mengatur posisi Y setelah MultiCell untuk menjaga kesejajaran baris
                $y1 = $this->GetY();

                $this->SetXY($x + $widths[0] + $widths[1] + $widths[2], $y);
                $this->Cell($widths[3], 6, $row['jam_masuk'], 1, 0, $aligns[3]);
                $this->Cell($widths[4], 6, $row['jam_keluar'], 1, 0, $aligns[4]);

                // Menyesuaikan posisi Y untuk baris berikutnya
                $this->SetY($y1);
                $this->Ln();
            }
        }
    }

    // Instansiasi kelas yang diturunkan
    $pdf = new PDF();
    $header = array('Tanggal', 'UID', 'Nama', 'Jam Masuk', 'Jam Keluar');
    $data = $pdf->LoadData();
    $pdf->SetFont('Arial', '', 12);
    $pdf->AddPage();
    $pdf->BasicTable($header, $data);
    $pdf->Output();

} elseif ($type == 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=data_absensi.csv');

    $output = fopen('php://output', 'w');
    fputcsv($output, array('Tanggal', 'UID', 'Nama', 'Jam Masuk', 'Jam Keluar'));

    $sql = "SELECT data_absen.uid, DATE_FORMAT(tanggal, '%d-%m-%Y') AS tanggal, nama, division,
            MIN(CASE WHEN status='IN' THEN waktu END) jam_masuk,
            MAX(CASE WHEN status='OUT' THEN waktu END) jam_keluar
            FROM data_absen, data_pengurus
            WHERE data_absen.uid = data_pengurus.uid 
            GROUP BY data_absen.uid, tanggal, nama, division";

    $result = mysqli_query($link, $sql);
    while ($row = mysqli_fetch_assoc($result)) {
        fputcsv($output, $row);
    }
    fclose($output);
}

// Tutup koneksi
mysqli_close($link);
?>
