<!DOCTYPE html>
<html lang="en">

<head>
    <title>Tambah Transaksi</title>
    @include('template.header')
</head>

<body id="page-top">

    <!-- Page Wrapper -->
    <div id="wrapper">

        <!-- Sidebar -->
        @include('template.sidebar')
        <!-- End of Sidebar -->

        <!-- Content Wrapper -->
        <div id="content-wrapper" class="d-flex flex-column">

            <!-- Main Content -->
            <div id="content">

                <!-- Topbar -->
                @include('template.navbar')
                <!-- End of Topbar -->

                <!-- Begin Page Content -->
                <div class="container-fluid">
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800">Tambah Transaksi</h1>
                    </div>

                    <div class="my-3 p-3 bg-body shadow-sm" style="box-shadow: 0 0 10px rgba(0, 0, 0, 0.5); border-radius:15px;">

                        @if ($errors->any())
                            <div class="alert alert-danger">
                                <ul>
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <form method="POST" action="{{ url('penjualan') }}" id="penjualanForm">
                        @csrf
                        <input type="hidden" name="customer_id" value="{{ $customer->id }}">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <a href='{{ url('penjualan') }}' class="btn btn-secondary btn-sm"> < Kembali</a>
                                <div>Tanggal Transaksi : <span id="tanggalTransaksi"></span></div>
                            </div>
                            <div>
                                <label for="customer_nama" class="form-label">Customer :</label>
                                <span>{{ $customer->nama }}</span>
                            </div>
                            <div>
                                <label for="customer_nomor" class="form-label">Nomor :</label>
                                <span>{{ $customer->nomor }}</span>
                            </div>
                            <div>
                                <label for="customer_alamat" class="form-label">Alamat :</label>
                                <span>{{ $customer->alamat }}</span>
                            </div>
                        <div class="mb-3 row">
                            <label for="nama" class="col-sm-2 col-form-label">Nama Barang</label>
                            <div class="col-sm-4 d-flex justify-content-end">
                                <input type="text" class="form-control" id="searchBarang" placeholder="Pilih Barang" aria-label="Search">
                                <button type="button" class="btn btn-secondary" data-toggle="modal" data-target="#modalBarang">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </div>
                        <table class="table table-striped" id="selectedBarangTable">
                            <thead>
                                <tr class="text-center">
                                    <th class="col-md-1 text-center">No</th>
                                    <th class="col-md-3 text-center">Nama</th>
                                    <th class="col-md-2 text-center">Harga</th>
                                    <th class="col-md-2 text-center">Jumlah</th>
                                    <th class="col-md-2 text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @if(old('barang_id'))
                                    @foreach(old('barang_id') as $index => $barang_id)
                                        <tr class="text-center" data-id="{{ $barang_id }}">
                                            <td class="col-md-1 text-center">{{ $index + 1 }}</td>
                                            <td class="col-md-3 text-center">{{ old('barang_nama')[$index] }}</td>
                                            <td class="col-md-2 text-center">
                                                {{ number_format(old('harga_jual')[$index], 0, ',', '.') }}
                                                <input type="hidden" class="harga-barang" name="harga_jual[]" value="{{ old('harga_jual')[$index] }}">
                                            </td>
                                            <td class="col-md-2 text-center">
                                                <input type="number" class="form-control jumlah-barang" name="jumlah[]" value="{{ old('jumlah')[$index] }}">
                                            </td>
                                            <td class="col-md-2 text-center">
                                                <button type="button" class="btn btn-danger btn-sm deleteBarangBtn" data-id="{{ $barang_id }}">
                                                    <i class="fas fa-trash"></i> Hapus
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                @endif
                            </tbody>
                        </table>        
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card-header py-4 flex-row align-items-center justify-content-between" style="border-radius: 15px; background-color: cornflowerblue; color: white;">
                                <h6 class="m-0 font-weight-bold">Total Harga: <span id="totalHarga">Rp {{ number_format(old('total_harga', 0), 0, ',', '.') }}</span></h6>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div style="display: flex; align-items: center; margin-bottom: 10px;">
                                <label for="bayar" style="width: 80px; margin-right: 10px;">Bayar</label>
                                <input type="number" class="form-control" id="bayar" name="bayar" value="{{ old('bayar') }}">
                            </div>
                            <div style="display: flex; align-items: center; margin-bottom: 10px;">
                                <label for="kembali" style="width: 80px; margin-right: 10px;">Kembali</label>
                                <input type="text" class="form-control" id="kembali" name="kembali" value="{{ old('kembali') }}" readonly>
                            </div>
                        </div>
                    </div>
                    <div class="d-flex justify-content-end mt-3">
                        <button type="submit" class="btn btn-primary">Simpan Pembelian</button>
                    </div>
                </form>                    
            </div>
                <!-- /.container-fluid -->

            </div>
            <!-- End of Main Content -->

            <!-- Footer -->
            @include('template.footer')
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
    @include('template.modal_logout')

    @include('template.script')

    <script>
        $(document).ready(function() {
    // Tangkap klik pada tombol Pilih Barang di modal
    $(document).on('click', '.pilihBarangBtn', function() {
        var id = $(this).data('id');
        var nama = $(this).data('nama');
        var harga = $(this).data('harga');
        var jumlah = $(this).data('jumlah');

        // Periksa apakah barang sudah ada di tabel
        var exists = false;
        $('#selectedBarangTable tbody tr').each(function() {
            if ($(this).data('id') == id) {
                exists = true;
                return false; // Hentikan loop
            }
        });

        if (!exists) {
            // Format harga untuk ditampilkan
            var formattedHarga = parseFloat(harga).toLocaleString('id-ID', { style: 'currency', currency: 'IDR' });

            // Tambahkan barang yang dipilih ke tabel
            var newRow = `<tr class="text-center" data-id="${id}">
                            <td class="col-md-1 text-center"></td>
                            <td class="col-md-3 text-center">${nama}</td>
                            <td class="col-md-2 text-center">
                                ${formattedHarga}
                                <input type="hidden" class="harga-barang" name="harga_jual[]" value="${harga}">
                                <input type="hidden" name="barang_id[]" value="${id}">
                            </td>
                            <td class="col-md-2 text-center">
                                <input type="number" class="form-control jumlah-barang" name="jumlah[]" oninput="hitungTotal()">
                            </td>
                            <td class="col-md-2 text-center">
                                <button type="button" class="btn btn-danger btn-sm deleteBarangBtn" data-id="${id}">
                                    <i class="fas fa-trash"></i> Hapus
                                </button>
                            </td>
                        </tr>`;

            $('#selectedBarangTable tbody').append(newRow);

            // Update nomor urut di tabel
            updateNomorUrut();

            // Hitung total keseluruhan dari barang yang dipilih
            hitungTotal();

            // Tutup modal setelah memilih barang
            $('#modalBarang').modal('hide');
        } else {
            alert("Barang sudah dipilih.");
        }
    });

    // Tangkap perubahan pada input jumlah barang
    $(document).on('input', '.jumlah-barang', function() {
        // Hitung ulang total keseluruhan setelah nilai jumlah diubah
        hitungTotal();
    });

    // Tangkap klik pada tombol Hapus Barang di tabel
    $(document).on('click', '.deleteBarangBtn', function() {
        var row = $(this).closest('tr');

        // Hapus baris dari tabel tampilan
        row.remove();

        // Update nomor urut di tabel setelah menghapus baris
        updateNomorUrut();

        // Hitung ulang total keseluruhan setelah menghapus barang
        hitungTotal();
    });

    // Tangkap perubahan pada input bayar untuk menghitung nilai kembali
    $('#bayar').on('input', function() {
        hitungKembali();
    });

    // Fungsi untuk mengupdate nomor urut di tabel
    function updateNomorUrut() {
        $('#selectedBarangTable tbody tr').each(function(index) {
            $(this).find('td:eq(0)').text(index + 1);
        });
    }

    // Fungsi untuk menghitung total keseluruhan harga barang yang dipilih
    function hitungTotal() {
        var total = 0;

        $('#selectedBarangTable tbody tr').each(function() {
            var harga = parseFloat($(this).find('.harga-barang').val());
            var jumlah = parseFloat($(this).find('.jumlah-barang').val());

            // Periksa jika jumlah valid (bukan NaN atau kosong)
            if (!isNaN(jumlah) && jumlah > 0 && !isNaN(harga) && harga > 0) {
                total += harga * jumlah;
            }
        });

        // Tampilkan total di card header
        $('#totalHarga').text(total.toLocaleString('id-ID', { style: 'currency', currency: 'IDR' }));

        // Panggil fungsi hitungKembali untuk memperbarui nilai kembali
        hitungKembali();
    }

    // Fungsi untuk menghitung nilai kembali
    function hitungKembali() {
    var totalHarga = parseFloat($('#totalHarga').text().replace(/[^0-9,-]+/g, ""));
    var bayar = parseFloat($('#bayar').val());

    // Periksa jika nilai bayar valid
    if (!isNaN(bayar) && bayar >= totalHarga) {
        var kembali = bayar - totalHarga;
        if (kembali === 0) {
            $('#kembali').val('Uang Pas');
        } else {
            $('#kembali').val(kembali.toLocaleString('id-ID', { style: 'currency', currency: 'IDR' }));
        }
    } else {
        $('#kembali').val('');
    }
}
});
    </script>
        <script>
            var spanTanggal = document.getElementById('tanggalTransaksi');
            var tanggalSekarang = new Date();
            var options = { day: 'numeric', month: 'long', year: 'numeric' };
            var tanggalFormatted = tanggalSekarang.toLocaleDateString('id-ID', options);
            spanTanggal.textContent = tanggalFormatted;
        </script> 
    
    <!-- Modal Barang -->
    <div class="modal fade" id="modalBarang" tabindex="-1" role="dialog" aria-labelledby="modalBarangLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalBarangLabel">Pilih Barang</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <!-- FORM PENCARIAN -->
                <div class="pt-3 mr-3 d-flex justify-content-end">
                    <form id="searchForm" class="d-flex w-70">
                        <input id="searchInput" class="form-control me-1" type="search" name="katakunci"
                            value="{{ Request::get('katakunci') }}" placeholder="Masukkan nama barang" aria-label="Search">
                        <button id="searchButton" class="btn btn-secondary" type="button">
                            <i class="fas fa-search"></i>
                        </button>
                    </form>
                </div>
                <div class="modal-body">
                    <table class="table table-striped" id="barangTable">
                        <thead>
                            <tr class="text-center">
                                <th class="col-md-1 text-center">No</th>
                                <th class="col-md-3 text-center">Nama</th>
                                <th class="col-md-2 text-center">Harga</th>
                                <th class="col-md-2 text-center">Jumlah</th>
                                <th class="col-md-2 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($barang as $item)
                                <tr class="text-center">
                                    <td class="col-md-1 text-center">{{ $loop->iteration }}</td>
                                    <td class="col-md-3 text-center">{{ $item->nama }}</td>
                                    <td class="col-md-2 text-center">Rp. {{ number_format($item->harga_jual, 0, ',', '.') }}</td>
                                    <td class="col-md-2 text-center">{{ $item->jumlah }}</td>
                                    <td class="col-md-2 text-center">
                                        <button type="button" class="btn btn-primary btn-sm pilihBarangBtn" data-id="{{ $item->id }}" data-nama="{{ $item->nama }}" data-harga="{{ $item->harga_jual }}" data-jumlah="{{ $item->jumlah }}">
                                            <i class="fas fa-check-square"></i> Pilih
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>                
            </div>
        </div>
    </div>

    <script>
        $(document).ready(function() {
    // Tangkap form pencarian saat tombol "Cari" ditekan
    $('#searchButton').on('click', function() {
        performSearch();
    });

    // Tangkap form pencarian saat tombol "Enter" ditekan di input
    $('#searchInput').on('keypress', function(e) {
        if (e.which === 13) {
            performSearch();
            return false; // Hindari submit form default
        }
    });

    // Fungsi untuk melakukan pencarian
    function performSearch() {
        var katakunci = $('#searchInput').val().toLowerCase();
        $('#barangTable tbody tr').filter(function() {
            $(this).toggle($(this).text().toLowerCase().indexOf(katakunci) > -1)
        });
    }

    // Bersihkan form pencarian saat modal ditutup
    $('#modalBarang').on('hidden.bs.modal', function () {
        $('#searchInput').val('');
        $('#barangTable tbody tr').show(); // Tampilkan kembali semua baris
    });
});

    </script>

</body>

</html>
