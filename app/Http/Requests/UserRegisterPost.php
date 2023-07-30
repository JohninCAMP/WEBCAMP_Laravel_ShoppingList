<?php
/**
 * Chapter15 v1.2.0「会員登録(簡易)追加」
 */
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\User as UserModel;

class UserRegisterPost extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name' => ['required', 'max:128'],
            'email' => ['required', 'email', 'max:254'],
            'password' => ['required','confirmed', 'max:72'],
        ];
    }
}