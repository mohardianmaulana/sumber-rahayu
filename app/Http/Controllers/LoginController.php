<?php
namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Session;

class LoginController extends Controller
{
    public function login()
    {
        
        return view('login');
        
    }

    public function actionlogin(Request $request)
    {
        $credentials = $request->only('email', 'password');

        if (Auth::attempt($credentials)) {
            
            if (Auth::user()->hasRole('owner')) {
                return redirect('dashboard')->with('success', 'Login Berhasil');; 
            } else {
                return redirect('dashboard')->with('success', 'Login Berhasil');;
            }
        } else {
            Session::flash('error', 'Email atau Password Salah');
            return redirect('/');
        }
    }

    public function actionlogout()
    {
        Auth::logout();
        return redirect('/');
    }

    public function register()
    {
        // Lakukan join antara tabel 'user' dan 'roles' untuk mendapatkan nama role
        $users = User::join('roles', 'user.roles_id', '=', 'roles.id')
            ->select('user.*', 'roles.name as roles_name')
            ->get();

        // Tampilkan view dengan data users
        return view('user.register', compact('users'));
    }

    
}