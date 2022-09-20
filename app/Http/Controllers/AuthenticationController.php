<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class AuthenticationController extends Controller
{
    public function index(Request $request)
    {
        return view("main.login");
    }

    public function postLogin(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "account" => "required",
            "password" => "required",
        ], [
            "account.required" => "Vui lòng nhập tài khoản",
            "password.required" => "Vui lòng nhập password",
        ]);

        if ($validator->fails()) {
            return redirect()->route("login")->with(["message.error" => $validator->messages()->first() ])->withInput();
        }

        $account = $request->account;
        $password = $request->password;

        foreach (config("authentication") as $auth) {
            if ($account == $auth["account"] && $password == $auth["password"]) {
                $request->session()->put("authenticated", $auth["account"]);
                \Log::channel('telegram')->info("Phát hiện đăng nhập mới {$request->ip()}");
                dd(1);
                return redirect()->route("crawl-data");
            }
        }
        return redirect()->route("login")->with(["message.error" => "Tài khoản hoặc mật khẩu không chính xác" ])->withInput();
    }

    public function logout(Request $request)
    {
        $request->session()->flush();
        return redirect()->route("login");
    }
}
