<?php

namespace App\Http\Controllers;

use App\Http\Requests\AuthRequest;
use App\Models\TaiKhoan;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

use Throwable;

class AuthController extends Controller
{
    //
    public function dangNhap()
    {
        return view('QuanLyKho.auth.login');
    }

    public function duyetDangNhap(AuthRequest $request)
    {
        try {
            // dd($request);
            $taiKhoan = TaiKhoan::query()
                ->where('MaNhanVien', '=', $request->post('MaNhanVien'))
                ->where('MatKhau', '=', $request->post('MatKhau'))
                ->first();
            // dd($taiKhoan);  
            if ($taiKhoan === null) {
                return redirect()->route('dangnhap')->with('error', 'Đăng nhập thất bại! Kiểm tra lại tài khoản hoặc mật khẩu!');
            } else {
                // dd($taiKhoan);
                // put sẽ ghi đè lên nếu nó tồn tại rồi
                session()->put('MaGiangVien', $taiKhoan->MaNhanVien);
                session()->put('Quyen', $taiKhoan->Quyen);
                if (session()->get('Quyen') === 0) {
                    return redirect()->route('sanpham.index');
                } else if (session()->get('Quyen') === 1 || session()->get('Quyen') === 2) {
                    return redirect()->route('dangnhap');
                } else {
                    return redirect()->route('dangnhap');
                }
            }
        } catch (Throwable $e) {
            return redirect()->route('dangnhap')->with('error', 'Đăng nhập thất bại! Kiểm tra lại tài khoản hoặc mật khẩu!');
        }
    }


    // Quên mật khẩu
    public function quenMatKhau()
    {
        return view('QuanLyKho.auth.email');
    }

    public function guiEmail(Request $request)
    {
        $request->validate([
            'Email' => 'required|email|exists:nhanvien',
        ]);

        // dd($request->Email);

        $token = Str::random(64);

        DB::table('quenmatkhau')->insert(
            ['Email' => $request->Email, 'Token' => $token, 'Created_at' => Carbon::now()]
        );

        Mail::send('QuanLyKho.auth.verify', ['Token' => $token], function ($message) use ($request) {
            $message->to($request->Email);
            $message->subject('Thông báo quên mật khẩu');
        });

        return back()->with('message', 'Chúng tôi đã gửi tin nhắn đến email khôi phục của bạn, vui lòng kiểm tra email!');
    }

    public function duyetXacThucEmail($token)
    {
        return view('QuanLyKho.auth.reset', ['token' => $token]);
    }

    public function doiMatKhau(Request $request)
    {

        $request->validate([
            'Email' => 'required|email|exists:nhanvien',
            'MatKhau' => 'required|string|min:6|confirmed',
            'MatKhau_confirmation' => 'required',
        ]);

        $xacThucTokenQuenMatKhau = DB::table('quenmatkhau')
            ->where(['Email' => $request->Email, 'Token' => $request->token])
            ->first();

        if (!$xacThucTokenQuenMatKhau) {
            return back()->withInput()->with('error', 'Invalid token!');
        } else {
            $nhanVien = DB::table('nhanvien')
                ->where(['Email' => $request->Email])
                ->first();
            if (!$nhanVien) {
                return back()->withInput()->with('error', 'Mã nhân viên của bạn không hợp lệ, liên hệ với người quản lý để biết thêm chi tiết!');
            } else {
                $doiMatKhau = DB::table('taikhoan')->where('MaNhanVien', $nhanVien->MaNhanVien)
                    ->update(['MatKhau' => $request->MatKhau]);
                // dd($doiMatKhau);
                DB::table('quenmatkhau')->where(['Email' => $request->Email])->delete();
                return redirect()->route('dangnhap')->with('success', 'Mật khẩu của bạn đã được cập nhật thành công!');
            }
        }
    }
}
