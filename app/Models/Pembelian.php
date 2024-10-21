<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Carbon\Carbon;

class Pembelian extends Model
{
    use HasFactory;
    protected $table = 'pembelian';
    protected $fillable = ['supplier_id', 'total_item', 'total_harga', 'tanggal_transaksi', 'user_id'];

    // Relasi ke model Barang
    public function barangs()
    {
        return $this->belongsToMany(Barang::class, 'barang_pembelian')
                    ->withPivot('jumlah', 'harga', 'jumlah_itemporary');
    }

    // Relasi ke model Supplier
    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    // Relasi ke model User
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Format tanggal transaksi
    public function getFormattedTanggalTransaksiAttribute()
    {
        return Carbon::parse($this->attributes['tanggal_transaksi'])->format('d-m-Y');
    }

    // Casting field tanggal_transaksi ke format date
    protected $casts = [
        'tanggal_transaksi' => 'date',
    ];

    // Method untuk mengambil data supplier dan kategori yang aktif
    public static function tambahBaru()
    {
        $supplier = Supplier::where('status', 1)->get();
        $kategori = Kategori::where('status', 1)->get();

        return [
            'supplier' => $supplier,
            'kategori' => $kategori,
        ];
    }

    // Method untuk menyimpan barang dan pembelian
    public static function storeBarang($request)
    {
        $validator = Validator::make($request->all(), [
            'nama' => 'required',
            'jumlah' => 'required|numeric|min:0',
            'harga_beli' => 'required|numeric|min:1',
            'harga_jual' => 'required|numeric|min:1',
            'minLimit' => 'required|numeric|min:1',
            'maxLimit' => [
                'required', 
                'numeric',
                'min:1',
                function ($attribute, $value, $fail) use ($request) {
                    if ($value < $request->minLimit) {
                        $fail('Max Limit tidak boleh lebih kecil daripada Min Limit');
                    }
                }
            ],
            'kategori_id' => 'required',
            'supplier_id' => 'required'
        ], [
            'nama.required' => 'Nama Barang wajib diisi',
            'jumlah.required' => 'Jumlah wajib diisi',
            'jumlah.min' => 'Jumlah tidak boleh kurang dari 0',
            'harga_beli.required' => 'Harga beli wajib diisi',
            'harga_beli.min' => 'Harga beli tidak boleh kurang dari 0',
            'harga_jual.required' => 'Harga jual wajib diisi',
            'harga_jual.min' => 'Harga jual tidak boleh kurang dari 0',
            'minLimit.required' => 'Min Limit wajib diisi',
            'minLimit.min' => 'Min Limit tidak boleh kurang dari 0',
            'maxLimit.required' => 'Max Limit wajib diisi',
            'maxLimit.min' => 'Max Limit tidak boleh kurang dari 0',
            'kategori_id.required' => 'Kategori wajib diisi',
            'supplier_id.required' => 'Supplier wajib diisi',
        ]);

        // Jika validasi gagal
        if ($validator->fails()) {
            return [
                'status' => 'error',
                'errors' => $validator->errors()
            ];
        }

        // Simpan barang
        $barang = self::create([
            'id_qr' => $request->id_qr,
            'nama' => $request->nama,
            'jumlah' => $request->jumlah,
            'harga_jual' => $request->harga_jual,
            'harga_beli' => $request->harga_beli,
            'minLimit' => $request->minLimit,
            'maxLimit' => $request->maxLimit,
            'kategori_id' => $request->kategori_id,
            'status' => 1,
        ]);

        // Hitung total harga
        $harga_beli = $request->harga_beli;
        $jumlah = $request->jumlah;
        $totalHarga = $harga_beli * $jumlah;

        // Simpan ke tabel pembelian
        $pembelian = Pembelian::create([
            'supplier_id' => $request->supplier_id,
            'total_item' => $jumlah,
            'total_harga' => $totalHarga,
            'tanggal_transaksi' => now(),
            'user_id' => Auth::id(),
        ]);

        // Simpan ke tabel pivot barang_pembelian
        $pembelian->barangs()->attach($barang->id, [
            'jumlah' => $jumlah, 
            'harga' => $harga_beli, 
            'jumlah_itemporary' => $jumlah, 
        ]);

        // Perbarui stok barang
        $barang->jumlah = $jumlah;
        $barang->save();

        // Periksa dan perbarui harga_barang jika perlu
        $hargaBarang = HargaBarang::where('barang_id', $barang->id)
            ->where('supplier_id', $request->supplier_id)
            ->whereNull('tanggal_selesai')
            ->first();

        if ($hargaBarang) {
            if ($hargaBarang->harga_beli != $harga_beli) {
                $hargaBarang->tanggal_selesai = now();
                $hargaBarang->save();

                HargaBarang::create([
                    'barang_id' => $barang->id,
                    'harga_beli' => $harga_beli,
                    'harga_jual' => $request->harga_jual,
                    'supplier_id' => $request->supplier_id,
                    'tanggal_mulai' => now(),
                    'tanggal_selesai' => null,
                ]);
            }
        } else {
            HargaBarang::create([
                'barang_id' => $barang->id,
                'harga_beli' => $harga_beli,
                'harga_jual' => $request->harga_jual,
                'supplier_id' => $request->supplier_id,
                'tanggal_mulai' => now(),
                'tanggal_selesai' => null,
            ]);
        }

        return [
            'status' => 'success',
            'message' => 'Produk berhasil ditambahkan'
        ];
    }

    // Menampilkan pembelian yang baru dalam 1 bulan
    public static function tampil()
    {
        $satuBulanLalu = Carbon::now()->subMonth();

        $pembelian = Pembelian::join('supplier', 'pembelian.supplier_id', '=', 'supplier.id')
            ->join('user', 'pembelian.user_id', '=', 'user.id')
            ->select('pembelian.*', 'supplier.nama as supplier_nama', 'user.name as user_nama')
            ->whereDate('pembelian.created_at', '>=', $satuBulanLalu)
            ->orderBy('pembelian.created_at', 'desc')
            ->get();

        return $pembelian;
    }

    // Menampilkan pembelian yang sudah lebih dari 1 bulan
    public static function tampilLama()
    {
        $satuBulanLalu = Carbon::now()->subMonth();

        $pembelian = Pembelian::join('supplier', 'pembelian.supplier_id', '=', 'supplier.id')
            ->join('user', 'pembelian.user_id', '=', 'user.id')
            ->select('pembelian.*', 'supplier.nama as supplier_nama', 'user.name as user_nama')
            ->whereDate('pembelian.created_at', '<=', $satuBulanLalu)
            ->orderBy('pembelian.created_at', 'desc')
            ->get();

        return $pembelian;
    }

    // Menyiapkan data untuk pembuatan pembelian
    public static function buat($supplier_id)
    {
        // Ambil semua barang
        $barang = Barang::all();

        // Ambil harga rata-rata beli barang
        $avgHargaBeli = DB::table('harga_barang')
            ->select('barang_id', DB::raw('ROUND(AVG(harga_beli)) as rata_rata_harga_beli'))
            ->whereNull('tanggal_selesai')
            ->groupBy('barang_id')
            ->get();

        $rataRataHargaBeli = [];
        foreach ($avgHargaBeli as $avg) {
            $rataRataHargaBeli[$avg->barang_id] = $avg->rata_rata_harga_beli;
        }

        // Ambil data supplier
        $supplier = Supplier::find($supplier_id);

        return [
            'barang' => $barang,
            'rataRataHargaBeli' => $rataRataHargaBeli,
            'supplier' => $supplier
        ];
    }

    // Method untuk menambah pembelian baru
    public static function tambahPembelian($data)
    {
        $totalHarga = 0;
        $totalItem = 0;

        // Hitung total harga dan total item
        foreach ($data['harga_beli'] as $index => $harga) {
            $jumlah = $data['jumlah'][$index];
            $totalHarga += $harga * $jumlah;
            $totalItem += $jumlah;
        }

        // Membuat data pembelian baru
        $pembelian = Pembelian::create([
            'supplier_id' => $data['supplier_id'],
            'total_item' => $totalItem,
            'total_harga' => $totalHarga,
            'tanggal_transaksi' => now(),
            'user_id' => Auth::id(),
        ]);

        // Menambahkan barang ke tabel pivot barang_pembelian
        foreach ($data['barang_id'] as $index => $barang_id) {
            $pembelian->barangs()->attach($barang_id, [
                'jumlah' => $data['jumlah'][$index],
                'harga' => $data['harga_beli'][$index],
                'jumlah_itemporary' => $data['jumlah'][$index],
            ]);
        }

        return $pembelian;
    }

    public static function ganti($id)
{
    $pembelian = Pembelian::with(['barangs', 'supplier', 'user'])->find($id);

    if (!$pembelian) {
        return null;  // Jika pembelian tidak ditemukan
    }

    $tanggalTransaksi = Carbon::parse($pembelian->tanggal_transaksi);
    $satuBulanLalu = Carbon::now()->subMonth();

    // Periksa apakah pembelian lebih dari satu bulan
    if ($tanggalTransaksi->lt($satuBulanLalu)) {
        return ['error' => 'Penjualan lebih dari satu bulan tidak dapat diedit.'];
    }

    // Ambil harga beli rata-rata untuk semua barang
    $avgHargaBeli = DB::table('harga_barang')
        ->select('barang_id', DB::raw('ROUND(AVG(harga_beli)) as rata_rata_harga_beli'))
        ->whereNull('tanggal_selesai')
        ->groupBy('barang_id')
        ->get();

    $rataRataHargaBeli = [];
    foreach ($avgHargaBeli as $avg) {
        $rataRataHargaBeli[$avg->barang_id] = $avg->rata_rata_harga_beli;
    }

    // Ambil semua barang untuk dipilih pada tampilan edit
    $barangs = Barang::all();

    // Kembalikan semua data yang dibutuhkan untuk tampilan edit
    return [
        'pembelian' => $pembelian,
        'rataRataHargaBeli' => $rataRataHargaBeli,
        'barangs' => $barangs,
        'tanggal_transaksi' => $pembelian->tanggal_transaksi->format('d-m-Y'),
    ];
}

public static function gantiPembelian($data, $id)
{
    $pembelian = Pembelian::find($id);

    $tanggalTransaksi = Carbon::parse($pembelian->tanggal_transaksi);
    $satuBulanLalu = Carbon::now()->subMonth();

    // Periksa apakah pembelian lebih dari satu bulan
    if ($tanggalTransaksi->lt($satuBulanLalu)) {
        return ['error' => 'Penjualan lebih dari satu bulan tidak dapat diedit.'];
    }

    // Validasi data tidak diperlukan di sini karena sudah dilakukan di controller
    $totalHarga = 0;
    $totalItem = 0;

    // Hitung total harga dan total item
    foreach ($data['harga_beli'] as $index => $harga) {
        $jumlah = $data['jumlah'][$index];
        $totalHarga += $harga * $jumlah;
        $totalItem += $jumlah;
    }

    // Perbarui detail pembelian
    $pembelian->update([
        'supplier_id' => $pembelian->supplier_id,
        'total_item' => $totalItem,
        'total_harga' => $totalHarga,
        'tanggal_transaksi' => $pembelian->tanggal_transaksi,
        'user_id' => Auth::id(),
    ]);

    // Sinkronisasi data pivot table
    $syncData = [];
    foreach ($data['barang_id'] as $index => $barang_id) {
        $harga = $data['harga_beli'][$index];
        $jumlah = $data['jumlah'][$index];

        // Ambil data pivot lama
        $pivotData = DB::table('barang_pembelian')
            ->where('barang_id', $barang_id)
            ->where('pembelian_id', $pembelian->id)
            ->first();

        $jumlah_itemporary = $pivotData ? $pivotData->jumlah_itemporary : 0;

        $syncData[$barang_id] = [
            'jumlah' => $jumlah,
            'harga' => $harga,
            'jumlah_itemporary' => $jumlah,
        ];

        // Perbarui jumlah barang
        $barang = Barang::find($barang_id);
        if ($pivotData) {
            // Logika untuk menyesuaikan jumlah barang
            if ($jumlah < $jumlah_itemporary) {
                $selisihJumlah = $jumlah_itemporary - $jumlah;
                $barang->jumlah -= $selisihJumlah;
            } else if ($jumlah > $jumlah_itemporary) {
                $selisihJumlah = $jumlah - $jumlah_itemporary;
                $barang->jumlah += $selisihJumlah;
            }
        } else {
            $barang->jumlah += $jumlah;
        }

        // Hanya simpan jika jumlah berubah
        if ($barang->isDirty('jumlah')) {
            $barang->save();
        }

        // Perbarui harga barang jika diperlukan
        $supplier_id = $data['supplier_id'] ?? $pembelian->supplier_id;
        $hargaBarang = HargaBarang::where('barang_id', $barang_id)
            ->where('supplier_id', $supplier_id)
            ->whereNull('tanggal_selesai')
            ->first();

        if ($hargaBarang && $harga != $hargaBarang->harga_beli) {
            $hargaBarang->update([
                'harga_beli' => $harga,
            ]);
        }
    }

    // Sinkronisasi data pivot
    $pembelian->barangs()->sync($syncData);

    return $pembelian;
}

}
