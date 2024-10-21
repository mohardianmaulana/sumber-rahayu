<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Barang;
use App\Models\Kategori;
use Illuminate\Support\Facades\Validator;
use App\Models\Supplier;
use Illuminate\View\View;
use App\Exports\PenanggalanExport;
use App\Exports\TokoExport;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\Penanggalan;
use App\Models\Persetujuan;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BarangController extends Controller
{
    public function index(Request $request)
    {
        // Memanggil method di model Barang untuk mendapatkan data barang
        $barang = Barang::getAllBarangWithKategoriAndHarga();
        
        // Memanggil method untuk mendapatkan rata-rata harga beli
        $rataRataHargaBeli = Barang::getAverageHargaBeli();

        // Mengambil semua data kategori dari tabel kategori
        $kategori = Kategori::all();

        // Mengembalikan view dengan data yang dibutuhkan
        return view('barang.index', compact('barang', 'kategori', 'rataRataHargaBeli'));
    }


public function arsip(Request $request)
    {
        // Memanggil method di model Barang untuk mendapatkan data barang
        $barang = Barang::arsip();

        // Memanggil method untuk mendapatkan rata-rata harga beli
        $rataRataHargaBeli = Barang::getAverageHargaBeli();
        
        // Mengambil semua data kategori dari tabel 'kategori'
        $kategori = Kategori::all();
        return view('barang.indexArsip', compact('barang', 'rataRataHargaBeli', 'kategori'));
    }

    public function pulihkan($id)
    {
        $barang = Barang::pulihkan($id);

        return redirect()->route('barang.lama')->with('success', 'Barang berhasil dipulihkan.');
    }

    public function arsipkan($id)
    {
        $barang = Barang::arsipkan($id);
        
        return redirect()->route('admin')->with('success', 'Barang berhasil diarsipkan.');
    }

    public function scanPage()
    {
        return view('scan');
    }

    // Memproses hasil scan
    public function cekQrCode(Request $request)
    {
        // Validasi input
        $request->validate([
            'id_qr' => 'required',
        ]);

        // Cek apakah QR code sudah ada di database
        $exists = Barang::where('id_qr', $request->id_qr)->exists();

        // Jika sudah ada, kirim response bahwa data sudah ada
        if ($exists) {
            return response()->json(['exists' => true]);
        } else {
            // Jika tidak ada, kirim response bahwa data belum ada
            return response()->json(['exists' => false]);
        }
    }

    public function create(Request $request)
{
    // Mengambil nilai id_qr dari request jika ada
    $id_qr = $request->input('id_qr');

    // Mengambil data supplier dan kategori dari database
    $supplier = Supplier::where('status', 1)->get();
    $kategori = Kategori::where('status', 1)->get();

    // Mengirimkan id_qr ke view jika ada
    return view('pembelian.create_barang', compact('kategori', 'supplier', 'id_qr'));
}


    public function store(Request $request) 
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

    if ($validator->fails()) {
        return redirect()->back()
                        ->withErrors($validator)
                        ->withInput();
    }

    // Simpan ke tabel barang
    $barang = Barang::create([
        'id_qr' => $request->id_qr,
        'nama' => $request->nama,
        'jumlah' => $request->jumlah,  // Jangan menambahkan jumlah di sini, biarkan sebagai jumlah input
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
    $barang->jumlah = $jumlah;  // Menggunakan jumlah input asli
    $barang->save();

    // Periksa dan perbarui harga_barang
    $hargaBarang = HargaBarang::where('barang_id', $barang->id)
        ->where('supplier_id', $request->supplier_id)
        ->whereNull('tanggal_selesai')
        ->first();

    if ($hargaBarang) {
        if ($hargaBarang->harga_beli != $harga_beli) {
            $hargaBarang->tanggal_selesai = now();
            $hargaBarang->save();

            // Buat baris baru dengan harga dan supplier baru
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

    return redirect()->to('pembelian')->with('success', 'Produk berhasil ditambahkan');
}


//     public function create()
//     {
//         $kategori = Kategori::all();
//         return view('barang.create',compact('kategori'));
//     }

//     public function store(Request $request)
// {
//     $validator = Validator::make($request->all(), [
//         'nama' => 'required',
//         'jumlah' => 'required|numeric|min:0',
//         'harga_jual' => 'required|numeric|min:1',
//         'minLimit' => 'required|numeric',
//         'maxLimit' => [
//             'required',
//             'numeric',
//             function ($attribute, $value, $fail) use ($request) {
//                 if ($value < $request->minLimit) {
//                     $fail('Max Limit tidak boleh lebih kecil daripada Min Limit');
//                 }
//             }
//         ],
//         'kategori_id' => 'required',
//     ], [
//         'nama.required' => 'Nama Barang wajib diisi',
//         'jumlah.required' => 'Jumlah wajib diisi',
//         'jumlah.min'=>'Jumlah tidak boleh kurang dari 0',
//         'harga_jual'=> 'Harga jual wajib diisi',
//         'minLimit.required' => 'Min Limit wajib diisi',
//         'maxLimit.required' => 'Max Limit wajib diisi',
//         'kategori_id.required' => 'Kategori wajib diisi',
//     ]);

//     if ($validator->fails()) {
//         return redirect()->back()
//                          ->withErrors($validator)
//                          ->withInput();
//     }

//     $barang = [
//         'nama' => $request->nama,
//         'jumlah' => $request->jumlah,
//         'harga_jual' => $request->harga_jual,
//         'minLimit' => $request->minLimit,
//         'maxLimit' => $request->maxLimit,
//         'kategori_id' => $request->kategori_id,
//     ];

//     Barang::create($barang);

//     return redirect()->to('barang')->with('success', 'Produk berhasil ditambahkan');
// }

public function checkEdit($id)
{
    $barang = Barang::find($id);
    $userId = Auth::id();
    $kerjaAksi = "update";
    $namaTabel = "Barang";
    $data = [
        'supplier_id' => null,
        'customer_id' => null,
        'kategori_id' => null,
        'barang_id' => $barang->id,
        'user_id' => $userId,
        'kerjaAksi' => $kerjaAksi,
        'namaTabel' => $namaTabel,
        'lagiProses' => 0,
        'kodePersetujuan' => null,
    ];

    $persetujuan = Persetujuan::where('barang_id', $barang->id)
        ->where('user_id', $userId)
        ->where('kerjaAksi', $kerjaAksi)
        ->where('namaTabel', $namaTabel)
        ->first();

    $persetujuanIsiForm = $persetujuan && $persetujuan->kodePersetujuan !== null;
    $persetujuanDisetujui = $persetujuanIsiForm && $persetujuan->lagiProses == 1;

    if (!$persetujuan) {
        $persetujuan = new Persetujuan;
        $persetujuan->fill($data);
        $persetujuan->timestamps = false;
        $persetujuan->save();
        return redirect()->to('/barang')->with('success', 'Persetujuan berhasil diajukan');
    } elseif ($persetujuanDisetujui) {
        return redirect()->route('barang.edit', $barang->id);
    } elseif ($persetujuanIsiForm) {
        return view('persetujuan.konfirmasi', compact('persetujuan'));
    } else {
        return redirect()->to('/barang')->with('info', 'Tunggu persetujuan dari owner.');
    }
}



public function edit($id)
{
    // Membuat subquery untuk mendapatkan harga terbaru dari tabel harga_barang
    $subquery = DB::table('harga_barang')
        ->select('barang_id', DB::raw('MAX(tanggal_mulai) as max_tanggal_mulai'))
        ->groupBy('barang_id');

    // Membuat query join antara tabel 'barang', 'kategori', dan 'harga_barang' menggunakan subquery
    $barang = Barang::join('kategori', 'barang.kategori_id', '=', 'kategori.id')
        ->joinSub($subquery, 'hb_latest', function($join) {
            $join->on('barang.id', '=', 'hb_latest.barang_id');
        })
        ->join('harga_barang', function($join) {
            $join->on('hb_latest.barang_id', '=', 'harga_barang.barang_id')
                ->on('hb_latest.max_tanggal_mulai', '=', 'harga_barang.tanggal_mulai');
        })
        ->select('barang.*', 'kategori.nama_kategori as kategori_nama', 'harga_barang.harga_beli', 'harga_barang.harga_jual')
        ->where('barang.id', $id)  // Tambahkan kondisi ini untuk memastikan hanya data yang sesuai ID yang diambil
        ->first();  // Menggunakan first() untuk mengambil satu hasil

    $kategori = Kategori::all();

    return view('barang.edit', compact('barang', 'kategori'));
}


    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'nama' => 'required',
            'minLimit' => 'required',
            'maxLimit' => [
                'required',
                'numeric',
                function ($attribute, $value, $fail) use ($request) {
                    if ($value < $request->minLimit) {
                        $fail('Max Limit tidak boleh lebih kecil daripada Min Limit');
                    }
                }
            ],
            'kategori_id' => 'required',
        ], [
            'nama.required'=>'Nama barang Barang wajib diisi',
            'minLimit.required'=>'Min Limit wajib diisi',
            'maxLimit.required'=>'Max Limit wajib diisi',
            'kategori_id.required'=>'Kategori wajib diisi',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                             ->withErrors($validator)
                             ->withInput();
        }

        $barang = [
            'nama'=>$request->nama,
            'minLimit'=>$request->minLimit,
            'maxLimit'=>$request->maxLimit,
            'kategori_id'=>$request->kategori_id,
        ];
        Barang::where('id', $id)->update($barang);
        $userId = Auth::id();
        Persetujuan::where('barang_id', $id)
            ->where('user_id', $userId)
            ->where('kerjaAksi', 'update')
            ->where('namaTabel', 'Barang')
            ->delete();
       return redirect()->to('barang')->with('success', 'Berhasil melakukan update data produk!');
    }

//     public function destroy($id)
//     {
//         Barang::where('id', $id)->delete();
//         return redirect()->to('barang')->with('success', 'Berhasil menghapus data produk');
//     }

//     public function barang(Request $request)
// {
//     $jumlahbaris = 10;
//     $kategori = Kategori::all();

//     // Buat query dasar dengan join.
//     $query = Toko::join('kategori', 'toko.kategori_id', '=', 'kategori.id')
//                  ->select('toko.*', 'kategori.nama_kategori as kategori_nama');

//     // Filter berdasarkan kategori jika diberikan
//     if ($request->has('kategori_id') && $request->kategori_id != '') {
//         $query->where('toko.kategori_id', $request->kategori_id);
//     }

//     // Jika kata kunci disediakan, tambahkan sebagai filter.
//     if ($request->has('katakunci') && strlen($request->katakunci)) {
//         $query->where('toko.nama', 'like', "%{$request->katakunci}%");
//     }

//     // Urutkan dan paginasi hasil query.
//     $data = $query->orderBy('toko.kode', 'asc')->paginate($jumlahbaris);

//     return view('barang.barang', ['data' => $data, 'kategori' => $kategori]);
// }

    // public function exportExcel()
    // {
    //     $tanggalDatabase = Penanggalan::orderBy('tanggal', 'asc')->first();

    //     $tanggalDatabase = $tanggalDatabase->tanggal;
    //     $tanggalSekarang = Carbon::now()->format('Y-m-d');
    //     if ($tanggalSekarang != $tanggalDatabase) {
    //         $fileName = 'toko' . Carbon::now()->format('Y_m_d_His') . '.xlsx';

    //         // Ekspor dan simpan file ke folder spesifik
    //         Excel::store(new TokoExport, $fileName, 'local');

    //         // Salin file ke folder tujuan
    //         copy(storage_path('app/' . $fileName), 'C:/Users/user/Backup_Rahayu/' . $fileName);
    //         // 1. Hapus semua data dari tabel 'tanggalan'
    //         DB::table('penanggalan')->delete();

    //         // 2. Tambahkan tanggal saat ini ke dalam tabel 'tanggalan'
    //         DB::table('penanggalan')->insert([
    //             'tanggal' => $tanggalSekarang,
    //             'created_at' => now(),
    //             'updated_at' => now(),
    //         ]);

    //     }
    //     return redirect('dashboard');
    // }
    public function generateCode($id)
    {
        // Generate 6 digit random code
        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        
        // Update the 'kodePersetujuan' field in 'persetujuan' table where 'id' equals $id
        $persetujuan = Persetujuan::find($id);
        if ($persetujuan) {
            // Temporarily disable timestamps
            $persetujuan->timestamps = false;
    
            // Update the kodePersetujuan field
            $persetujuan->kodePersetujuan = $code;
    
            // Save the changes without updating timestamps
            $persetujuan->save();
    
            return response()->json(['success' => true, 'kodePersetujuan' => $code]);
        } else {
            return response()->json(['success' => false, 'message' => 'Data persetujuan tidak ditemukan.'], 404);
        }
    }
    
}
