<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Persetujuan;
use Illuminate\Support\Facades\Validator;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SupplierController extends Controller
{
    public function index(Request $request)
    {
        // Mengambil data kategori yang statusnya 0
        $supplier = Supplier::where('status', 1)->get();
        
        return view('supplier.index', compact('supplier'));
    }

    public function arsip(Request $request)
    {
        // Mengambil data kategori yang statusnya 0
        $supplier = Supplier::where('status', 0)->get();
        
        return view('supplier.indexArsip', compact('supplier'));
    }

    public function pulihkan($id)
    {
        $supplier = Supplier::find($id);
        if ($supplier) {
            $supplier->status = 1;
            $supplier->save();
        }

        return redirect()->route('supplier.lama')->with('success', 'supplier berhasil dipulihkan.');
    }

    public function arsipkan($id)
    {
        $supplier = Supplier::find($id);
        if ($supplier) {
            $supplier->status = 0;
            $supplier->save();
        }

        return redirect()->route('supplier')->with('success', 'supplier berhasil diarsipkan.');
    }

    public function create() 
    {
        return view('supplier.create');
    }

    public function checkEdit($id)
    {
        $supplier = Supplier::find($id);
        $userId = Auth::id();
        $kerjaAksi = "update";
        $namaTabel = "Supplier";
        $data = [
            'supplier_id' => $supplier->id,
            'customer_id' => null,
            'kategori_id' => null,
            'barang_id' => null,
            'user_id' => $userId,
            'kerjaAksi' => $kerjaAksi,
            'namaTabel' => $namaTabel,
            'lagiProses' => 0,
            'kodePersetujuan' => null,
        ];
    
        $persetujuan = Persetujuan::where('supplier_id', $supplier->id)
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
            return redirect()->to('/supplier')->with('success', 'Persetujuan berhasil diajukan');
        } elseif ($persetujuanDisetujui) {
            return redirect()->route('supplier.edit', $supplier->id);
        } elseif ($persetujuanIsiForm) {
            return view('persetujuan.konfirmasi', compact('persetujuan'));
        } else {
            return redirect()->to('/supplier')->with('info', 'Tunggu persetujuan dari owner.');
        }
    }
    

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nama' => 'required',
            'nomor' => 'required',
            'alamat' => 'required',
        ], [
            'nama.required'=>'Nama supplier wajib diisi',
            'nomor.required'=>'Nomor HP wajib diisi',
            'alamat.required'=>'Alamat wajib diisi',
        ]);
        
        if ($validator->fails()) {
            return redirect()->back()
                             ->withErrors($validator)
                             ->withInput();
        }

        $supplier = [
            'nama'=>$request->nama,
            'nomor'=>$request->nomor,
            'alamat'=>$request->alamat,
            'status' => 1, // Set status to 1
        ];
        Supplier::create($supplier);
       return redirect()->to('supplier')->with('success', 'Supplier berhasil ditambahkan');
    }

    public function edit($id)
    {
        $supplier = Supplier::where('id', $id)->first();
        return view('supplier.edit')->with('supplier', $supplier);
    }
    
    public function update(Request $request, $id)
    {
        $request->validate([
            'nama' => 'required',
            'nomor' => 'required',
            'alamat' => 'required',
        ], [
            'nama.required'=>'Nama supplier wajib diisi',
            'nomor.required'=>'Nomor HP wajib diisi',
            'alamat.required'=>'Alamat wajib diisi',
        ]);
        $supplier = [
            'nama'=>$request->nama,
            'nomor'=>$request->nomor,
            'alamat'=>$request->alamat,
        ];
        Supplier::where('id', $id)->update($supplier);
        $userId = Auth::id();
        Persetujuan::where('supplier_id', $id)
            ->where('user_id', $userId)
            ->where('kerjaAksi', 'update')
            ->where('namaTabel', 'Supplier')
            ->delete();
       return redirect()->to('supplier')->with('success', 'Supplier berhasil ditambahkan');
    }

    public function destroy($id)
    {
        Supplier::where('id', $id)->delete();
        return redirect()->to('supplier')->with('success', 'Berhasil menghapus data supplier');
    }

    public function profil($kode) 
{
    $supplier = Supplier::where('kode', $kode)->get();
    return view('supplier.profil')->with('supplier', $supplier);
}

}
