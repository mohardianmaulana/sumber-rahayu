<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Persetujuan;
use Illuminate\Support\Facades\Validator;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        // Mengambil data customer yang statusnya 1
        $customers = Customer::where('status', 1)->get();
        
        return view('customer.index', compact('customers'));
    }

    public function arsip(Request $request)
    {
        // Mengambil data customer yang statusnya 0
        $customers = Customer::where('status', 0)->get();
        
        return view('customer.indexArsip', compact('customers'));
    }

    public function pulihkan($id)
    {
        $customer = Customer::find($id);
        if ($customer) {
            $customer->status = 1;
            $customer->save();
        }

        return redirect()->route('customer.lama')->with('success', 'Customer berhasil dipulihkan.');
    }

    public function arsipkan($id)
    {
        $customer = Customer::find($id);
        if ($customer) {
            $customer->status = 0;
            $customer->save();
        }

        return redirect()->route('customer')->with('success', 'Customer berhasil diarsipkan.');
    }

    public function create() 
    {
        return view('customer.create');
    }

    public function checkEdit($id)
    {
        $customer = Customer::find($id);
        $userId = Auth::id();
        $kerjaAksi = "update";
        $namaTabel = "Customer";
        $data = [
            'supplier_id' => null,
            'customer_id' => $customer->id,
            'kategori_id' => null,
            'barang_id' => null,
            'user_id' => $userId,
            'kerjaAksi' => $kerjaAksi,
            'namaTabel' => $namaTabel,
            'lagiProses' => 0,
            'kodePersetujuan' => null,
        ];
    
        $persetujuan = Persetujuan::where('customer_id', $customer->id)
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
            return redirect()->to('/customer')->with('success', 'Persetujuan berhasil diajukan');
        } elseif ($persetujuanDisetujui) {
            return redirect()->route('customer.edit', $customer->id);
        } elseif ($persetujuanIsiForm) {
            return view('persetujuan.konfirmasi', compact('persetujuan'));
        } else {
            return redirect()->to('/customer')->with('info', 'Tunggu persetujuan dari owner.');
        }
    }
    

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nama' => 'required',
            'nomor' => 'required',
            'alamat' => 'required',
        ], [
            'nama.required'=>'Nama customer wajib diisi',
            'nomor.required'=>'Nomor HP wajib diisi',
            'alamat.required'=>'Alamat wajib diisi',
        ]);
        
        if ($validator->fails()) {
            return redirect()->back()
                             ->withErrors($validator)
                             ->withInput();
        }

        $customer = [
            'nama'=>$request->nama,
            'nomor'=>$request->nomor,
            'alamat'=>$request->alamat,
            'status' => 1, // Set status to 1
        ];
        Customer::create($customer);
       return redirect()->to('customer')->with('success', 'Customer berhasil ditambahkan');
    }

    public function edit($id)
    {
        $customer = Customer::where('id', $id)->first();
        return view('customer.edit')->with('customer', $customer);
    }
    
    public function update(Request $request, $id)
    {
        $request->validate([
            'nama' => 'required',
            'nomor' => 'required',
            'alamat' => 'required',
        ], [
            'nama.required'=>'Nama customer wajib diisi',
            'nomor.required'=>'Nomor HP wajib diisi',
            'alamat.required'=>'Alamat wajib diisi',
        ]);
        $customer = [
            'nama'=>$request->nama,
            'nomor'=>$request->nomor,
            'alamat'=>$request->alamat,
        ];
        Customer::where('id', $id)->update($customer);
        $userId = Auth::id();
        Persetujuan::where('supplier_id', $id)
            ->where('user_id', $userId)
            ->where('kerjaAksi', 'update')
            ->where('namaTabel', 'Customer')
            ->delete();
       return redirect()->to('customer')->with('success', 'Customer berhasil ditambahkan');
    }

    public function destroy($id)
    {
        Customer::where('id', $id)->delete();
        return redirect()->to('customer')->with('success', 'Berhasil menghapus data customer');
    }
}
