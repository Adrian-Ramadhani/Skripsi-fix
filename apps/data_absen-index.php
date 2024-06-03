<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">
    <link rel="icon" href="logo.PNG" type="image/x-icon">

    <title>BEM Fasilkom Unsika - Absensi</title>

    <!-- Custom fonts for this template-->
    <link href="../src/vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">

    <!-- Custom styles for this template-->
    <link href="../src/css/sb-admin-2.min.css" rel="stylesheet">

    <!-- Custom styles for this page -->
    <link href="../src/vendor/datatables/dataTables.bootstrap4.min.css" rel="stylesheet">

    <!-- Bootstrap core JavaScript-->
    <script src="../src/vendor/jquery/jquery.min.js"></script>
    <script src="../src/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script type="text/javascript">
        $(document).ready(function(){
            $('[data-toggle="tooltip"]').tooltip();
        });
    </script>
</head>

<body id="page-top">
    <!-- Page Wrapper -->
    <div id="wrapper">
        <!-- Sidebar -->
        <?php include 'partial_sidebar.php';?>
        <!-- End of Sidebar -->

        <!-- Content Wrapper -->
        <div id="content-wrapper" class="d-flex flex-column">
            <!-- Main Content -->
            <div id="content">
                <!-- Topbar -->
                <?php include 'partial_topbar.php';?>
                <!-- End of Topbar -->

                <!-- Begin Page Content -->
                <div class="container-fluid">
                    <!-- Page Heading -->
                    <h1 class="h3 mb-2 text-gray-800">Data Absensi</h1>

                    <!-- DataTales Example -->
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Data Absensi Harian</h6>
                        </div>
                        <div class="card-body">
                            <div class="col-md-12">
                                <div class="row">
                                    <div class="col-md-6">
                                        <form action="data_absen-index.php" method="get">
                                            <div class="form-row">
                                                <div class="col">
                                                    <input type="text" class="form-control" placeholder="Pencarian data absensi" name="search">
                                                </div>
                                                <div class="col">
                                                    <input type="date" class="form-control" name="date">
                                                </div>
                                                <div class="col">
                                                    <button type="submit" class="btn btn-primary">Filter</button>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                    <div class="col-md-6 text-right">
                                        <a href="export.php?type=pdf" class="btn btn-danger">Download PDF</a>
                                        <a href="export.php?type=csv" class="btn btn-success">Download CSV</a>
                                        <form action="delete_all.php" method="post" style="display:inline;">
                                            <button type="submit" class="btn btn-warning">Hapus Semua Data</button>
                                        </form>
                                    </div>
                                </div>
                                <br>

                                <div class='table-responsive'>
                                    <table class='table table-striped'>
                                        <thead>
                                            <tr>
                                                <th><a href="?search=<?php echo $search ?>&order=tanggal&sort=<?php echo $sort ?>">Tanggal</a></th>
                                                <th><a href="?search=<?php echo $search ?>&order=uid&sort=<?php echo $sort ?>">UID</a></th>
                                                <th><a href="?search=<?php echo $search ?>&order=nama&sort=<?php echo $sort ?>">Nama</a></th>
                                                <th>Jam Masuk</th>
                                                <th>Jam Keluar</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            // Include config file
                                            require_once "config.php";

                                            // Column sorting on column name
                                            $orderBy = array('tanggal', 'uid', 'nama'); 
                                            $order = 'nama';
                                            if (isset($_GET['order']) && in_array($_GET['order'], $orderBy)) {
                                                $order = $_GET['order'];
                                            }

                                            // Column sort order
                                            $sortBy = array('asc', 'desc'); 
                                            $sort = 'desc';
                                            if (isset($_GET['sort']) && in_array($_GET['sort'], $sortBy)) {
                                                if ($_GET['sort'] == 'asc') {                                                                                                                            
                                                    $sort = 'desc';
                                                } else {
                                                    $sort = 'asc';
                                                }
                                            }

                                            // Build SQL query
                                            $sql = "SELECT data_absen.uid, DATE_FORMAT(tanggal, '%d-%m-%Y') AS tanggal, nama, division,
                                                    MIN(CASE WHEN status='IN' THEN waktu END) jam_masuk,
                                                    MAX(CASE WHEN status='OUT' THEN waktu END) jam_keluar
                                                    FROM data_absen, data_pengurus
                                                    WHERE data_absen.uid = data_pengurus.uid";
                                            
                                            if (!empty($_GET['search'])) {
                                                $search = $_GET['search'];
                                                $sql .= " AND CONCAT(tanggal, data_absen.uid, nama) LIKE '%$search%'";
                                            }
                                            if (!empty($_GET['date'])) {
                                                $date = $_GET['date'];
                                                $sql .= " AND DATE(tanggal) = '$date'";
                                            }

                                            $sql .= " GROUP BY data_absen.uid, tanggal, nama, division
                                                      ORDER BY $order $sort";

                                            if ($result = mysqli_query($link, $sql)) {
                                                if (mysqli_num_rows($result) > 0) {
                                                    while ($row = mysqli_fetch_array($result)) {
                                                        echo "<tr>";
                                                        echo "<td>" . $row['tanggal'] . "</td>";
                                                        echo "<td>" . $row['uid'] . "</td>";
                                                        echo "<td>" . $row['nama'] . "</td>";
                                                        echo "<td>" . $row['jam_masuk'] . "</td>";
                                                        echo "<td>" . $row['jam_keluar'] . "</td>";
                                                        echo "</tr>";
                                                    }
                                                    // Free result set
                                                    mysqli_free_result($result);
                                                } else {
                                                    echo "<tr><td colspan='5'>No records were found.</td></tr>";
                                                }
                                            } else {
                                                echo "<tr><td colspan='5'>ERROR: Could not execute $sql. " . mysqli_error($link) . "</td></tr>";
                                            }

                                            // Close connection
                                            mysqli_close($link);
                                            ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
                <!-- /.container-fluid -->
            </div>
            <!-- End of Main Content -->

            <!-- Footer -->
            <footer class="sticky-footer bg-white">
                <div class="container my-auto">
                    <div class="copyright text-center my-auto">
                        <span>Copyright &copy; Adrian Ramadhani</span>
                    </div>
                </div>
            </footer>
            <!-- End of Footer -->
        </div>
        <!-- End of Content Wrapper -->
    </div>
    <!-- End of Page Wrapper -->

    <!-- Scroll to Top Button-->
    <a class="scroll-to-top rounded" href="#page-top">
        <i class="fas fa-angle-up"></i>
    </a>

    <!-- Logout Modal-->
    <div class="modal fade" id="logoutModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Ready to Leave?</h5>
                    <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>
                <div class="modal-body">Select "Logout" below if you are ready to end your current session.</div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" type="button" data-dismiss="modal">Cancel</button>
                    <a class="btn btn-primary" href="logout.php">Logout</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Core plugin JavaScript-->
    <script src="../src/vendor/jquery-easing/jquery.easing.min.js"></script>

    <!-- Custom scripts for all pages-->
    <script src="../src/js/sb-admin-2.min.js"></script>

    <!-- Page level plugins -->
    <script src="../src/vendor/datatables/jquery.dataTables.min.js"></script>
    <script src="../src/vendor/datatables/dataTables.bootstrap4.min.js"></script>

    <!-- Page level custom scripts -->
    <script src="../src/js/demo/datatables-demo.js"></script>
</body>
</html>

<!-- delete_all.php -->
<?php
require_once "config.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $sql = "DELETE FROM data_absen";
    if (mysqli_query($link, $sql)) {
        header("location: data_absen-index.php");
        exit();
    } else {
        echo "ERROR: Could not able to execute $sql. " . mysqli_error($link);
    }

    // Close connection
    mysqli_close($link);
}
?>
