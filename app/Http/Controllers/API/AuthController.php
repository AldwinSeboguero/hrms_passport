<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Auth;
use App\Models\BioStation;
class AuthController extends Controller
{
    //
    public function register(Request $request){
        $validator = Validator::make($request->all(),[
            "name" => "required",
            "email" => "required|email",
            "password" => "required",
            "confirm_password" => "reuired|same:password"
        ]);
        if($validator->fails()){
            return response()->json([
                "status" => 0,
                "message" => "validation error",
                "data" => $validator->errors()->all(),
            ]);
        }
        return response()->json([
            "status" => 1,
        ]);
    }
    public function login(Request $request){
        if(Auth::attempt(["email" => $request->email,"password" => $request->password] ) && BioStation::where('hwid', $request->hwid)->first()){
        // dd(BioStation::where('hwid', $request->hwid)->first());

            $user = Auth::user();
            $response = [];
            $response["token"] = $user->createToken("ParSUHRMS")->accessToken;
            $response["user"] =$user->name;
            $response["email"] =$user->email;
            
            return response()->json([
                "status" => 1,
                "message" => "login success",
                "data" => $response,
            ]);

        }
        return response()->json([
            "status" => 0,
        ]);
    }
}
