<?php

namespace App\Http\Controllers;

use App\Consts\ControllerConsts;
use App\Models\User;
use App\Models\WardManager;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Closure;

class UserController extends Controller
{
    /**
     * ユーザータイプ種別
     * 
     * @var array
     */
    private $userTypes = [

        '000' => [
            'name' => '承認待機',
            'class' => 'bg-gray-300 text-gray-900 text-sm font-medium mr-2 px-2.5 py-1 rounded-full'
        ],
        '777' => [
            'name' => 'スーパー管理者',
            'class' => 'bg-pink-300 text-pink-900 text-sm font-medium mr-2 px-2.5 py-1 rounded-full'
        ],
        '007' => [
            'name' => '管理者',
            'class' => 'bg-sky-300 text-sky-900 text-sm font-medium mr-2 px-2.5 py-1 rounded-full'
        ],
        '005' => [
            'name' => '病棟管理者',
            'class' => 'bg-green-300 text-green-900 text-sm font-medium mr-2 px-2.5 py-1 rounded-full'
        ],
        '001' => [
            'name' => 'スタッフ',
            'class' => 'bg-indigo-300 text-indigo-900 text-sm font-medium mr-2 px-2.5 py-1 rounded-full'
        ],
        '009' => [
            'name' => '非承認',
            'class' => 'bg-orange-300 text-orange-900 text-sm font-medium mr-2 px-2.5 py-1 rounded-full'
        ],
    ];

    /**
     * ユーザー一覧画面
     * 
     * @return \Illuminate\View\View
     */
    public function userIndex() {
        $users = User::where('user_type', '!=', '000')
        ->orderBy('id', 'desc')
        ->paginate(ControllerConsts::PAGINATION_COUNT);
        $userTypes = $this->userTypes;
        return view('smart.user-info', compact('users', 'userTypes'));
    }

    /**
     * ユーザー権限削除
     * 
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function revokePermission($id) {
        
        $user = User::findOrFail($id);
        $user->user_type = '000';
        $user->save();
        return redirect()->back()->with('success', 'ユーザーの権限が削除されました');
    }

    /**
     * ユーザー承認画面
     * 
     * @return \Illuminate\View\View
     * @throws \Exception
     * @throws \Throwable
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function userApproval() {
        
        $users = User::with('wardManager')
        ->where('user_type', '000')
        ->orderBy('id', 'desc')
        ->paginate(ControllerConsts::PAGINATION_COUNT);
        $userTypes = $this->userTypes;
        return view('smart.user-approval', compact('users', 'userTypes'));
    }

    /**
     * ユーザー承認
     * 
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     * @throws \Exception
     */
    public function userApprovalRegistration(Request $request, $id) {
        $user = User::find($id);
        if ($user && $user->user_type === '000') {
            // user_type入力値を検証
            $userType = $request->input('user_type');
            if (is_null($userType)) {
                // user_type入力値がない場合, エラーメッセージを返す
                return response()->json(['error' => 'ユーザータイプがある存在しません。'], 400);
            }
            // 入力値が有効な場合、user_typeを更新
            $user->user_type = $userType;
            $user->approval_date = now();
            $user->approval_user = auth()->id();
            $user->save();
            return response()->json(['success' => '承認しました'], 200);
        }
        return response()->json(['error' => '承認できませんでした'], 404);
    }

    /**
     * 病棟管理者画面
     * 
     * @return \Illuminate\View\View
     */
    public function wardManager() {

        $users = User::with('wardManager')
        ->where('user_type', '005')
        ->orderBy('id', 'desc')
        ->paginate(ControllerConsts::PAGINATION_COUNT);

        return view('smart.ward-manager', compact('users'));
    }

    /**
     * 病棟更新
     * 
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     * @throws \Exception
     */
    public function updateWard(Request $request, $id) {
        try {
            Log::info('updateWard called', ['user_id' => $id, 'ward_codes' => $request->input('ward_codes', [])]);
            $wardCodes = $request->input('ward_codes', []);

            WardManager::where('user_id', $id)
                ->whereNotIn('ward_code', $wardCodes)
                ->delete();

            foreach ($wardCodes as $wardCode) {
                WardManager::updateOrCreate(
                    ['user_id' => $id, 'ward_code' => $wardCode],
                    ['user_id' => $id, 'ward_code' => $wardCode,
                    'creator_id' => auth()->id()]
                );
            }
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            Log::error('Error updating ward', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => '病棟更新中エラーが発生しました。', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * 病棟管理者の病棟取得
     * 
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function getWardManager($id) {

        $wardCodes = WardManager::where('user_id', $id)
            ->pluck('ward_code')
            ->toArray();

        return response()
            ->json(['ward_codes' => $wardCodes]);
            
    }

}