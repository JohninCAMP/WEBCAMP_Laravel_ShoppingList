<?php
declare(strict_types=1);
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\ShoppingListRegisterPostRequest;
use App\Models\ShoppingList as ShoppingListModel;
use App\Models\CompletedShoppingList as CompletedShoppingListModel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ShoppingListController extends Controller
{
    /**
     * 買うもの一覧ページ を表示する
     *
     * @return \Illuminate\View\View
     */
    public function list()
    {
        // 1Page辺りの表示アイテム数を設定
        $per_page = 3;

        // 一覧の取得
        $list = $this->getListBuilder()
                     ->paginate($per_page);

        //
        return view('shoppinglist.list', ['list' => $list]);
    }

    /**
     * 買うものの新規登録
     */
    public function register(ShoppingListRegisterPostRequest $request)
    {
        // validate済みのデータの取得
        $datum = $request->validated();

        // user_id の追加
        $datum['user_id'] = Auth::id();

        // テーブルへのINSERT
        try {
            $r = ShoppingListModel::create($datum);
        } catch(\Throwable $e) {
            // XXX 本当はログに書く等の処理をする。今回は一端「出力する」だけ
            echo $e->getMessage();
            exit;
        }

        // 買うもの登録成功
        $request->session()->flash('front.shoppinglist_register_success', true);

        //
        return redirect('/shopping_list/list');
    }

    /**
     * 買うものの詳細閲覧
     */
    public function detail($shoppinglist_id)
    {
        //
        return $this->singleShoppingListRender($shoppinglist_id, 'shoppinglist.detail');
    }

    /**
     * 買うものの編集画面表示
     */
    public function edit($shoppinglist_id)
    {
        //
        return $this->singleShoppingListRender($shoppinglist_id, 'shoppinglist.edit');
    }

    /**
     * 「単一の買うもの」Modelの取得
     */
    protected function getShoppingListModel($shoppinglist_id)
    {
        // shoppinglist_idのレコードを取得する
        $shoppinglist = ShoppingListModel::find($shoppinglist_id);
        if ($shoppinglist === null) {
            return null;
        }
        // 本人以外の買うものならNGとする
        if ($shoppinglist->user_id !== Auth::id()) {
            return null;
        }
        //
        return $shoppinglist;
    }

    /**
     * 「単一の買うもの」の表示
     */
    protected function singleShoppingListRender($shoppinglist_id, $template_name)
    {
        // shoppinglist_idのレコードを取得する
        $shoppinglist = $this->getShoppingListModel($shoppinglist_id);
        if ($shoppinglist === null) {
            return redirect('/shopping_list/list');
        }

        // テンプレートに「取得したレコード」の情報を渡す
        return view($template_name, ['shoppinglist' => $shoppinglist]);
    }


    /**
     * 買うものの編集処理
     */
    public function editSave(ShoppingListRegisterPostRequest $request, $shoppinglist_id)
    {
        // formからの情報を取得する(validate済みのデータの取得)
        $datum = $request->validated();

        // shoppinglist_idのレコードを取得する
        $shoppinglist = $this->getShoppingListModel($shoppinglist_id);
        if ($shoppinglist === null) {
            return redirect('/shopping_list/list');
        }

        // レコードの内容をUPDATEする
        $shoppinglist->name = $datum['name'];
        $shoppinglist->period = $datum['period'];
        $shoppinglist->detail = $datum['detail'];
        $shoppinglist->priority = $datum['priority'];
/*
        // 可変変数を使った書き方(参考)
        foreach($datum as $k => $v) {
            $shoppinglist->$k = $v;
        }
*/
        // レコードを更新
        $shoppinglist->save();

        // 買うもの編集成功
        $request->session()->flash('front.shoppinglist_edit_success', true);
        // 詳細閲覧画面にリダイレクトする
        return redirect(route('detail', ['shoppinglist_id' => $shoppinglist->id]));
    }

    /**
     * 削除処理
     */
    public function delete(Request $request, $shoppinglist_id)
    {
        // shoppinglist_idのレコードを取得する
        $shoppinglist = $this->getShoppingListModel($shoppinglist_id);

        // 買うものを削除する
        if ($shoppinglist !== null) {
            $shoppinglist->delete();
            $request->session()->flash('front.shoppinglist_delete_success', true);
        }

        // 一覧に遷移する
        return redirect('/shopping_list/list');
    }
    /**
     * 買うものの完了
     */
    public function complete(Request $request, $shoppinglist_id)
    {
        /* 買うものを完了テーブルに移動させる */
        try {
            // トランザクション開始
            DB::beginTransaction();

            // shoppinglist_idのレコードを取得する
            $shoppinglist = $this->getShoppingListModel($shoppinglist_id);
            if ($shoppinglist === null) {
                // shoppinglist_idが不正なのでトランザクション終了
                throw new \Exception('');
            }
            // shoppinglists側を削除する

            $shoppinglist->delete();

            // completed_shoppinglists側にinsertする
            $dask_datum = $shoppinglist->toArray();
            unset($dask_datum['created_at']);
            unset($dask_datum['updated_at']);
            $r = CompletedShoppingListModel::create($dask_datum);
            if ($r === null) {
                // insertで失敗したのでトランザクション終了
                throw new \Exception('');
            }

            // トランザクション終了
            DB::commit();
            // 完了メッセージ出力
            $request->session()->flash('front.shoppinglist_completed_success', true);
        } catch(\Throwable $e) {
            // トランザクション異常終了
            DB::rollBack();
            // 完了失敗メッセージ出力
            $request->session()->flash('front.shoppinglist_completed_failure', true);
        }

        // 一覧に遷移する
        return redirect('/shopping_list/list');
    }

    /**
     * 一覧用の Illuminate\Database\Eloquent\Builder インスタンスの取得
     */
    protected function getListBuilder()
    {
        return ShoppingListModel::where('user_id', Auth::id())
                     ->orderBy('name');
    }

}