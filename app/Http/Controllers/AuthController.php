<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use App\User;
use App\Message;

class AuthController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api', ['except' => ['login', 'register']]);
    }

    /**
     * New user registration
     */
    public function register(Request $request)
    {
        $ret                 = [];
        $ret['success']      = 0;

        $validator = Validator::make($request->all(), [
            'username'  => 'bail|required|string|min:6|max:20|regex:/^[a-zA-Z]+\w*/i|unique:users,username',
            'password'  => 'bail|required|string|regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)[\s\S]{6,20}$/',
            'email'     => 'bail|required|email:rfc,dns|unique:users,email',
            'mobile'    => 'bail|required|regex:/^09\d{8}$/|unique:users,mobile'
        ]);

        if ($validator->fails()) {

            $errors = $validator->errors();

            // 帳號錯誤分級
            foreach ($errors->get('username') as $message) {
                switch ($message) {
                    case 'The username has already been taken.':
                        $ret['erroCode']     = 1005;
                        $ret['errorMessage'] = '帳號已被使用';
                        break;
                    default:
                        $ret['erroCode']     = 1001;
                        $ret['errorMessage'] = '帳號格式不符';
                }

                return response()->json($ret);
            }

            // 密碼格式錯誤分級
            if ($errors->get('password')) {
                $ret['erroCode']     = 1002;
                $ret['errorMessage'] = '密碼格式不符合';

                return response()->json($ret);
            }

            // 手機格式錯誤分級
            foreach ($errors->get('mobile') as $message) {
                switch ($message) {
                    case 'The mobile has already been taken.':
                        $ret['erroCode']     = 1006;
                        $ret['errorMessage'] = '手機已被使用';
                        break;
                    default:
                        $ret['erroCode']     = 1003;
                        $ret['errorMessage'] = '手機格式不符合';
                }

                return response()->json($ret);
            }

            // Email格式錯誤分級
            foreach ($errors->get('email') as $message) {
                switch ($message) {
                    case 'The email has already been taken.':
                        $ret['erroCode']     = 1007;
                        $ret['errorMessage'] = '此email已被註冊';
                        break;
                    default:
                        $ret['erroCode']     = 1004;
                        $ret['errorMessage'] = 'email格式不符合';
                }

                return response()->json($ret);
            }

        }

        $ret['success'] = 1;

        $user = User::create([
            'username' => $request->get('username'),
            'email'    => $request->get('email'),
            'password' => Hash::make($request->get('password')),
            'name'     => $request->get('name'),
            'mobile'   => $request->get('mobile'),
        ]);

        $ret['token'] = auth()->login($user);

        return response()->json($ret);
    }

    public function login()
    {
        $ret = [];
        $ret['success'] = 0;

        $credentials = request(['username','password']);

        $count = User::where(function($q) use ($credentials) {
            $q->where('username',  $credentials['username'])
              ->orWhere('mobile',  $credentials['username']);
        })->count();

        if (!$count) {
           $ret['errorCode']    = 4001;
           $ret['errorMessage'] = '無此帳號';

           return response()->json($ret);
        }

        if (! $token = auth()->attempt($credentials)) {
            $ret['errorCode']    = 4002;
            $ret['errorMessage'] = '密碼錯誤';

            return response()->json($ret);
        }

        $ret['success'] = 1;
        $ret['token']   = $token;

        return response()->json($ret);
    }

    public function message(Request $request)
    {
        $ret     = [];
        $message = $request->input('message');
        $user    = auth()->user();
        $message_model          = new Message;
        $message_model->message = $m;
        $message_model->uid     = $user->id;
        $message_model->parent  = 0;

        if (!$message_model->save()) {
            $ret['success']      = 0;
            $ret['errorCode']    = 9999;
            $ret['errorMessage'] = '系統錯誤';

            return response()->json($ret);
        }

        $ret['success']   = 1;
        $ret['messageId'] = $message_model->id;

        return response()->json($ret);
    }

    public function reply(Request $request)
    {
        $ret             = [];
        $ret['success']  = 0;
        $user            = auth()->user();
        $message_id      = $request->input('message_id');
        $reply           = $request->input('reply');
        $message_model   = Message::find($message_id);

        if (!$message_model) {
            $ret['errorCode']    = 7002;
            $ret['errorMessage'] = '無此留言ID，回覆失敗';

            return response()->json($ret);
        }

        if ($message_model->parent) {
            $ret['errorCode']    = 7002;
            $ret['errorMessage'] = '無此留言ID，回覆失敗';

            return response()->json($ret);
        }

        $reply_model          = new Message;
        $reply_model->message = $reply;
        $reply_model->uid     = $user->id;
        $reply_model->parent  = $message_model->id;

        if (!$reply_model->save()) {
            $ret['errorCode']    = 9999;
            $ret['errorMessage'] = '系統錯誤';

            return response()->json($ret);
        }

        $ret['success'] = 1;
        $ret['replyId'] = $reply_model->id;

        return response()->json($ret);
    }

}
