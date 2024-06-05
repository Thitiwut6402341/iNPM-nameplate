<?php

namespace App\Http\Controllers;

use App\Http\Libraries\JWT\JWTUtils;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Login;

class LoginController extends Controller
{
    private $jwtUtils;

    public function __construct()
    {
        $this->jwtUtils = new JWTUtils();
    }

    // Login
    function login(Request $request)
    {
        try {
            $validators = Validator::make(
                $request -> all(),
                [
                    'username' => 'required | string | min:1 | max:100',
                    'password' => 'required | string | min:1 | max:255',
                ]
            );

            if ($validators -> fails()) {
                return response()->json([
                    "status" => "error",
                    "message" => "Bad request",
                    "data" => [
                        [
                            "validator" => $validators -> errors()
                        ]
                    ]
                        ], 400);
            }

            // $validator->validated() = {"username": "x","password":"x"}
            $validated = (object)$validators -> validated();
            $username = strtolower($validated -> username);
            $password = $validated -> password;


            // Check LDAP Login
            $ldapServer = 'snc-former.com';
            $ldapConnect = \ldap_connect($ldapServer);
            if (!$ldapConnect) return response()->json([
                "status" => "error",
                "message" => "Error in LDAP Connection",
                "data" => []
            ]);

            $userLDAP = $username . '@' . $ldapServer;
            $ldapLogin = @\ldap_bind($ldapConnect, $userLDAP, $password);
            if (!$ldapLogin) return response()->json([
                "status" => "error",
                "message" => "No username or password in LDAP",
                "data" => []
            ]);

            // Check iNPM login
            $user = Login::where('username', $username) -> take(1) -> get();
            // if username doesn't exist
            if (\count($user) == 0) return response() -> json([
                "status" => "error",
                "message" => "There is no user in the iNPM system",
                "data" => []
            ]);

            \date_default_timezone_set('Asia/Bangkok');
            $dt = new \DateTime();
            $payload = array(
                "username" => $user[0] -> username,
                "name" => $user[0] -> user,
                "iat" => $dt -> getTimestamp(),
                "exp" => $dt -> modify('+ 24hours') -> getTimestamp(),
            );

            $token = $this->jwtUtils -> generateToken($payload);
            return response() -> json([
                "status" => "success",
                "message" => "Login success",
                "data" => [
                    [
                        "username" => $user[0] -> username,
                        "name" => json_decode($user[0] -> name),
                        "token" => $token,
                    ]
                ]
            ]);

        } catch (\Exception $e){
            return response() -> json([
                "status" => "error",
                "message" => $e -> getMessage(),
                "data" => []] , 500);
        }
    }
}
