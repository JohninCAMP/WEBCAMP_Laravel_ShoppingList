<?php
/**
 * Chapter15 v1.2.0「会員登録(簡易)追加」
 */
declare(strict_types=1);
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\UserRegisterPost;
use App\Models\User as UserModel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
     /**
      * 表示用
      */
    public function index()
    {
        return view('/user/register');
    }
    /**
     * 登録用
     */
    public function register(UserRegisterPost $request)
    {
        // validate済みのデータの取得
        $datum = $request->validated();

        // user_id の追加
        $datum['user_id'] = Auth::id();
        //
        $datum['email_verified_at'] = date('Y-m-d H:i:s');
        // $datum の passowrd の値を、ハッシュ化されたものと置き換える
        $datum['password'] = Hash::make($datum['password']);

        // テーブルへのINSERT
        try {
            $r = UserModel::create($datum);
        } catch(\Throwable $e) {
            // XXX 本当はログに書く等の処理をする。今回は一端「出力する」だけ
            echo $e->getMessage();
            exit;
        }

        // タスク登録成功
        $request->session()->flash('front.user_register_success', true);

        //
        return redirect('/');
    }

}