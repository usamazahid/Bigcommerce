<?php

namespace App\Http\Controllers;

use Illuminate\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AuthController extends BaseController
{
    public function user_verification(Request $r){
		$store_hash = $r->input(['store_hash']);
		$user_id = $r->input(['user_id']);
    	$password = md5($r->input(['password']));
        
        $data =  DB::table('users')->where(['username'=>$user_id, 'password'=>$password])->get();

        if(isset($data[0]->mid)){
        	$query = DB::table('bigcommerce_app_installs')->where('store_hash', $store_hash)->update(['verified'=> 1, 'mxc_id'=>$data[0]->mid]);
        	if($query){
			return response()->json(['Success'=>'Login Successfull...'], 200);	      		
        	}
        }else{
        	return response()->json(['error'=>'Invalid Login Details...'], 200);
        }

    }
}
