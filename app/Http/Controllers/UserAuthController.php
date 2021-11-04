<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Mail\Gmail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Redirect;

class UserAuthController extends Controller
{
    public function register(Request $request)
    {
        $data = ($request->json()->all());

        $data['password'] = bcrypt($request->password);
        $user = new User();
        $result = $user->userRegister($data);

        if ($result->count() > 0) {
            return response("NErr");
        }

        $userInfo = User::create($data);

        $encrypt_method = "AES-256-CBC";
        $secret_key = '7aE3OKIZxusugQdpk3gwNi9x63MRAFLgkMJ4nyil88ZYMyjqTSE3FIo8L5KJghfi';
        $secret_iv = '7aE3OKIZxusugQdpk3gwNi9x63MRAFLgkMJ4nyil88ZYMyjqTSE3FIo8L5KJghfi';
        $key = hash('sha256', $secret_key);

        $iv = substr(hash('sha256', $secret_iv), 0, 16);

        $string = json_encode(array(["name"=>$data["name"], "email"=>$data["email"], "password"=>$data["password"]]));
        $encryptToken = openssl_encrypt($string, $encrypt_method, $key, 0, $iv);
        $encryptToken = base64_encode($encryptToken);

        $to_email = $data['email'];
        $to_name = $data['name'];

        Mail::to($to_email)->send(new Gmail(['token'=>$encryptToken]));

        if (Mail::failures()) {
            return response('VErr');
        }

        return response(['user' => $userInfo]);
    }

    public function verified(Request $request) {

        $data = ($request->query());

        $encrypt_method = "AES-256-CBC";
        $secret_key = '7aE3OKIZxusugQdpk3gwNi9x63MRAFLgkMJ4nyil88ZYMyjqTSE3FIo8L5KJghfi';
        $secret_iv = '7aE3OKIZxusugQdpk3gwNi9x63MRAFLgkMJ4nyil88ZYMyjqTSE3FIo8L5KJghfi';
        $key = hash('sha256', $secret_key);

        $iv = substr(hash('sha256', $secret_iv), 0, 16);
        $decryptToken = openssl_decrypt(base64_decode($data["token"]), $encrypt_method, $key, 0, $iv);
        $decryptTokenArray = json_decode($decryptToken, 1);

        $user = new User();
        $user->verified($decryptTokenArray[0]);
        
        return response('OK');
    }

    public function login(Request $request)
    {
        $data = ($request->json()->all());

        if (!auth()->attempt($data)) {
            return response(['error_message' => 'Incorrect Details. 
            Please try again']);
        }

        $token = auth()->user()->createToken('API Token')->accessToken;

        return response(['user' => auth()->user(), 'token' => $token]);
    }

    public function home(Request $request) {
        return response(['user' => auth()->user()]);
    }
}