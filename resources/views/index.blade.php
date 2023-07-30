@extends('layout')

{{-- メインコンテンツ --}}
@section('contets')
        <h1>ログイン</h1>
        @if (session('front.user_register_success') == true)
            ユーザーを登録しました！！<br>
        @endif

        @if ($errors->any())
            <div>
            @foreach ($errors->all() as $error)
                {{ $error }}<br>
            @endforeach
            </div>
        @endif
        <form action="/login" method="post">
            @csrf
            email：<input name="email" value="{{ old('email') }}"><br>
            パスワード：<input  name="password" type="password"><br>
            <button>ログインする</button>

            {{-- Chapter15 v1.2.0「会員登録(簡易)追加」 --}}
            <br>
            <a href="/user/register">会員登録</a><br>

        </form>
@endsection