<?php

namespace App\Http\Controllers\BackOffice;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\BackOfficeUserRoles;
use App\Events\BackOfficeUserLogEvent;

class RoleController extends Controller
{
    private $token,$emailUserLogin;
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('authLoginBackOffice',['only' => ['saveRole','updateRole','deleteRole']]);
        $this->middleware('onlyJson',['only'=>['getDataRoleBySearch','saveRole','updateRole']]);
        $this->token = request()->token;
        $token = \App\BackOfficeUserTokens::where('token',$this->token)->first();
        $dataUser = $token != '' ? \App\BackOfficeUsers::where('_id',$token->idUserBackOffice)->first() : null;
        $this->emailUserLogin = $dataUser != null ? $dataUser->emailUser : null;
    }

    public function getDataRole()
    {
        try {
            $roles = BackOfficeUserRoles::orderBy('_id','desc')->get();
            if ($roles) {
                $response = response()->json(['statusCode' => '000', 'message' => 'Sukses', 'data' => $roles]);
            } else {
                $response = response()->json(['statusCode' => '111', 'message' => 'Gagal', 'data' => null]);
            }
        } catch (\Exception $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
        return $response;
    }

    public function getDataRoleFirstPage($take)
    {
        try {
            $roles = BackOfficeUserRoles::skip(0)->take((int)$take)->orderBy('_id','desc')->get();
            $totalData = BackOfficeUserRoles::count();
            if ($roles) {
                $response = response()->json(['statusCode' => '000', 'message' => 'Sukses', 'data' => $roles, 'total' => $totalData]);
            } else {
                $response = response()->json(['statusCode' => '111', 'message' => 'Gagal', 'data' => null]);
            }
        } catch (\Exception $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
        return $response;
    }

    public function getDataRoleByPage($take,$page)
    {
        $skip = ($take*$page)-$take;
        try {
            $roleById = BackOfficeUserRoles::skip($skip)->take((int)$take)->orderBy('_id','desc')->get();
            $totalData = BackOfficeUserRoles::count();
            if ($roleById) {
                $response = response()->json(['statusCode' => '000', 'message' => 'Sukses', 'data' => $roleById, 'total' => $totalData]);
            } else {
                $response =  response()->json(['statusCode' => '111', 'message' => 'Gagal']);
            }
        } catch (\Exception $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
        return $response;
    }

    public function getDataRoleBySearch(Request $req)
    {
        $val = str_replace(' ','',$req->search);
        
        try {
            $result = BackOfficeUserRoles::where('namaRole','like','%'.$val.'%')->orderBy('_id','desc')->get();
            
            if ($result) {
                $response = response()->json(['statusCode' => '000', 'message' => 'Sukses', 'data' => $result]);
            } else {
                $response =  response()->json(['statusCode' => '111', 'message' => 'Gagal']);
            }
        } catch (\Exception $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
        return $response;
    }

    public function saveRole(Request $req)
    {
        $namaRole = $req->namaRole;
        $listMenu = $req->listMenu;
        
        try {
            $totalDataMenu = count($listMenu);
            $arrBaru = [];
            for($i=0;$i<$totalDataMenu;$i++){
                $arrBaru[] =['noMenu'=>$listMenu[$i]['noMenu'],'namaMenu'=>$listMenu[$i]['namaMenu'],'urlMenu'=>$listMenu[$i]['urlMenu'],'iconMenu'=>$listMenu[$i]['iconMenu']];
            }
            $role = new BackOfficeUserRoles;

            $role->namaRole = $namaRole;
            $role->listMenu = $arrBaru;

            if ($role->save()) {
                $response = json_encode(['statusCode' => '000', 'message' => 'Sukses']);
            } else {
                $response = json_encode(['statusCode' => '446', 'message' => 'Gagal Update Role User Back Office']);
            }

        } catch (\Exception $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
        event(new BackOfficeUserLogEvent($this->emailUserLogin,'Role','Add Role - '.$namaRole,json_decode($response)->message));
        return $response;
    }

    public function updateRole(Request $req, $id)
    {
        $namaRole = $req->input('namaRole');
        $listMenu = $req->listMenu != '' ? $req->listMenu : [];
        
        try {
            $totalDataMenu = count($listMenu);
            $arrBaru = [];
            for($i=0;$i<$totalDataMenu;$i++){
                $arrBaru[] =['noMenu'=>$listMenu[$i]['noMenu'],'namaMenu'=>$listMenu[$i]['namaMenu'],'urlMenu'=>$listMenu[$i]['urlMenu'],'iconMenu'=>$listMenu[$i]['iconMenu']];
            }
            
            $data = array(
                'namaRole' => $namaRole,
                'listMenu' => $arrBaru
            );

            if (BackOfficeUserRoles::where('_id', $id)->update($data)) {
                $response = json_encode(['statusCode' => '000', 'message' => 'Sukses']);
            } else {
                $response = json_encode(['statusCode' => '447', 'message' => 'Gagal Update Role User Back Office']);
            }

        } catch (\Exception $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
        event(new BackOfficeUserLogEvent($this->emailUserLogin,'Role','Update Role - '.$namaRole,json_decode($response)->message));
        return $response;
    }

    public function deleteRole($id)
    {
        try {
            $dataRole = BackOfficeUserRoles::where('_id', $id)->first();
            $namaRole = $dataRole->namaRole;

            if (BackOfficeUserRoles::where('_id', $id)->delete()) {
                $response = json_encode(['statusCode' => '000', 'message' => 'Sukses']);
            } else {
                $response = json_encode(['statusCode' => '448', 'message' => 'Gagal Delete Role User Back Office']);
            }

        } catch (\Exception $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
        event(new BackOfficeUserLogEvent($this->emailUserLogin,'Role','Delete Role - '.$namaRole,json_decode($response)->message));
        return $response;
    }

    public function listMenus()
    {
        try {
            $menus = \App\BackOfficeMenus::orderBy('_id','asc')->get();
            $totalData = \App\BackOfficeMenus::count();
            
            if ($menus) {
                $response = response()->json(['statusCode' => '000', 'message' => 'Sukses', 'data' => $menus, 'total' => $totalData]);
            } else {
                $response = response()->json(['statusCode' => '111', 'message' => 'Gagal', 'data' => null]);
            }
        } catch (\Exception $e) {
            $errorCode = $e->getCode();
            $message = $e->getMessage();
            \Sentry\captureException($e);
            $response = json_encode(array('statusCode' => $errorCode, 'message' => $message));
        }
        return $response;
    }
}
