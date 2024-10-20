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
        ->where('barang.status', 0) // Menambahkan kondisi where untuk barang dengan status 1
        ->whereNotNull('harga_barang.harga_jual') // Menambahkan kondisi where untuk harga_jual yang tidak null
        ->select('barang.id', 'barang.nama', 'barang.kategori_id', 'kategori.nama_kategori as kategori_nama', DB::raw('MIN(harga_barang.harga_beli) as harga_beli'), 'harga_barang.harga_jual', 'barang.jumlah', 'barang.minLimit', 'barang.maxLimit') // Pastikan minLimit dan maxLimit disertakan
        ->groupBy('barang.id', 'barang.nama', 'barang.kategori_id', 'kategori.nama_kategori', 'harga_barang.harga_jual', 'barang.jumlah', 'barang.minLimit', 'barang.maxLimit') // Tambahkan minLimit dan maxLimit di sini juga
        ->get();

    // Menghitung rata-rata harga_beli untuk setiap barang_id dengan tanggal_selesai null
    $avgHargaBeli = DB::table('harga_barang')
        ->select('barang_id', DB::raw('ROUND(AVG(harga_beli)) as rata_rata_harga_beli'))
        ->whereNull('tanggal_selesai')
        ->groupBy('barang_id')
        ->get();

    // Menyimpan hasil rata-rata ke dalam array
    $rataRataHargaBeli = [];
    foreach ($avgHargaBeli as $avg) {
        $rataRataHargaBeli[$avg->barang_id] = $avg->rata_rata_harga_beli;
    }

    // Mengambil semua data kategori dari tabel 'kategori'
    $kategori = Kategori::all();
        
        return view('barang.indexArsip', compact('barang', 'rataRataHargaBeli', 'kategori'));
    }

    public function pulihkan($id)
    {
        $barang = Barang::find($id);
        if ($barang) {
            $barang->status = 1;
            $barang->save();
        }

        return redirect()->route('barang.lama')->with('success', 'Barang berhasil dipulihkan.');
    }

    public function arsipkan($id)
    {
        $barang = Barang::find($id);
        if ($barang) {
            $barang->status = 0;
            $barang->save();
        }

        return redirect()->route('admin')->with('success', 'Barang berhasil diarsipkan.');
    }



    public function create()
    {
        $kategori = Kategori::all();
        return view('barang.create',compact('kategori'));
    }

    public function store(Request $request)
{
    $validator = Validator::make($request->all(), [
        'nama' => 'required',
        'jumlah' => 'required|numeric|min:0',
        'harga_jual' => 'required|numeric|min:1',
        'minLimit' => 'required|numeric',
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
        'nama.required' => 'Nama Barang wajib diisi',
        'jumlah.required' => 'Jumlah wajib diisi',
        'jumlah.min'=>'Jumlah tidak boleh kurang dari 0',
        'harga_jual'=> 'Harga jual wajib diisi',
        'minLimit.required' => 'Min Limit wajib diisi',
        'maxLimit.required' => 'Max Limit wajib diisi',
        'kategori_id.required' => 'Kategori wajib diisi',
    ]);

    if ($validator->fails()) {
        return redirect()->back()
                         ->withErrors($validator)
                         ->withInput();
    }

    $barang = [
        'nama' => $request->nama,
        'jumlah' => $request->jumlah,
        'harga_jual' => $request->harga_jual,
        'minLimit' => $request->minLimit,
        'maxLimit' => $request->maxLimit,
        'kategori_id' => $request->kategori_id,
    ];

    Barang::create($barang);

    return redirect()->to('barang')->with('success', 'Produk berhasil ditambahkan');
}

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

    public function destroy($id)
    {
        Barang::where('id', $id)->delete();
        return redirect()->to('barang')->with('success', 'Berhasil menghapus data produk');
    }

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

    public function exportExcel()
    {
        $tanggalDatabase = Penanggalan::orderBy('tanggal', 'asc')->first();

        $tanggalDatabase = $tanggalDatabase->tanggal;
        $tanggalSekarang = Carbon::now()->format('Y-m-d');
        if ($tanggalSekarang != $tanggalDatabase) {
            $fileName = 'toko' . Carbon::now()->format('Y_m_d_His') . '.xlsx';

            // Ekspor dan simpan file ke folder spesifik
            Excel::store(new TokoExport, $fileName, 'local');

            // Salin file ke folder tujuan
            copy(storage_path('app/' . $fileName), 'C:/Users/user/Backup_Rahayu/' . $fileName);
            // 1. Hapus semua data dari tabel 'tanggalan'
            DB::table('penanggalan')->delete();

            // 2. Tambahkan tanggal saat ini ke dalam tabel 'tanggalan'
            DB::table('penanggalan')->insert([
                'tanggal' => $tanggalSekarang,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

        }
        return redirect('dashboard');
    }
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
