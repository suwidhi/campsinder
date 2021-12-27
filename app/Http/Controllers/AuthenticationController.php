<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use App\Mail\EmailOtpController;
use Illuminate\Support\Facades\Redis;
use App\Http\Controllers\Session;

class AuthenticationController extends Controller
{
    public function createUsers(Request $request) {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'username' => 'required|unique:users|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8'
        ], [
            'name.required' => 'Pastikan memasukan nama anda!',
            'name.max' => 'Nama anda terlalu panjang!',

            'username.required' => 'Pastikan memasukan username anda!',
            'username.max' => 'Username anda terlalu panjang!',
            'username.unique' => 'Username sudah ada!',

            'email.required' => 'Email diperlukan untuk login!',
            'email.unique' => 'Email sudah digunakan!',
            'email.max' => 'Email terlalu panjang!',

            'password.required' => 'Pastikan mengisi password anda!',
            'password.min' => 'Password minimal 8 karakter!',
        ]);
        
        if ($validator->fails()){
            return response()
            ->json([
                'status'=> 'error',
                'errors'=> $validator->errors()
            ]);
        } else {
            $redis = Redis::connection();
            
            $otp_code = rand(0,9) . rand(0,9) . rand(0,9) . rand(0,9);
            $redis->hset('users', 'test', 'admin');
            $details = [
    
                'title' => 'Jangan beritahukan kode ini demi keamanan!',
                'body' => 'KODE OTP: ' . $otp_code
        
            ];
            Mail::to($request->email)->send(new EmailOtpController($details));
            if (Mail::failures()){
                return response()->json([
                    'status'=> 'error',
                    'message'=> 'Server gagal mengirim email!'
                ]);
            } else {
                $user = User::create([
                    'name' => $request->name,
                    'username' => $request->username,
                    'email' => $request->email,
                    'password' => Hash::make($request->password)
                 ]);
        
                $token = $user->createToken('auth_token')->plainTextToken;
    
                return response()
                    ->json([
                        'user'=> $user,
                        'token'=> $token
                    ]);
            }
        }
    }

    public function generateEmailOtp(Request $request){
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8'
        ], [
            'email.required' => 'Email diperlukan untuk login!',
            'email.unique' => 'Email sudah digunakan!',
            'email.max' => 'Email terlalu panjang!',
            'email.valid' => 'Email harus benar!',

            'password.required' => 'Pastikan mengisi password anda!',
            'password.min' => 'Password minimal 8 karakter!',
        ]);

        if ($validator->fails()){
            return response()
            ->json([
                'status'=> 'error',
                'errors'=> $validator->errors()
            ]);
        } else {
            $otp_code = rand(0,9) . rand(0,9) . rand(0,9) . rand(0,9);
            $details = [
    
                'title' => 'Jangan beritahukan kode ini demi keamanan!',
                'body' => 'KODE OTP: ' . $otp_code
        
            ];
            // Mengirim Email ke user
            Mail::to($request->email)->send(new EmailOtpController($details));

            if (Mail::failures()){
                return response()->json([
                    'status'=> 'error',
                    'message'=> 'Server gagal mengirim email!'
                ]);
            } else {
                // Menyimpan Email Dan Password Untuk Disimpan Jika Verifikasi OTP Berhasil
                session([
                    'email' => $request->email,
                    'password' => $request->password
                ]);

                // Simpan OTP Ke Redis
                $redis = Redis::connection();
                $redis->hset('usersOtp:'.$request->email, 'otp', $otp_code);
                $redis->expire('usersOtp:'.$request->email, 90);

                return response()->json([
                    'status'=> 'success',
                    'message' => 'OTP Berhasil Dikirim, Periksa Email Anda! Jika tidak ada pada inbox, Tolong periksa pada spam!'
                ])->header('x-laravel-session', $request->session()->getId());
            }
        }

    }

    public function verifyOtpCode(Request $request){

        $email = session('email');

        $redis = Redis::connection();
        $code = $redis->hget('usersOtp:' . $email, 'otp');

        if ($code===$request->otp){
            return response()->json([
                'status'=> 'success',
                'message' => 'Kode OTP Benar!'
            ]);
        } else {
            return response()->json([
                'status'=> 'failed',
                'message' => 'Kode OTP Salah!',
                'otp'=>[
                    'redis' => $code,
                    'user' => $request->otp,
                    'email' => $email
                ]
            ]);
        }
    }

    // public function login(Request $request)
    // {
    //     if (!Auth::attempt($request->only('email', 'password')))
    //     {
    //         return response()
    //             ->json(['message' => 'Unauthorized'], 401);
    //     }

    //     $user = User::where('email', $request['email'])->firstOrFail();

    //     $token = $user->createToken('auth_token')->plainTextToken;

    //     return response()
    //         ->json(['message' => 'Hi '.$user->name.', welcome to home','access_token' => $token, 'token_type' => 'Bearer', ]);
    // }

    // // method for user logout and delete token
    // public function logout()
    // {
    //     auth()->user()->tokens()->delete();

    //     return [
    //         'message' => 'You have successfully logged out and the token was successfully deleted'
    //     ];
    // }
}
