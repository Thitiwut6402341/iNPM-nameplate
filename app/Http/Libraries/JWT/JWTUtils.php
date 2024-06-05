<?php

namespace App\Http\Libraries\JWT;

use App\Http\Libraries\JWT\JWT;
use App\Http\Libraries\JWT\Key;

// define("PRIVATE_KEY", "<Secret Key>");
define("PRIVATE_KEY", env('JWT_SECRET'));

class JWTUtils
{
     public function generateToken($payload)
     {
          $token = JWT::encode($payload, PRIVATE_KEY, 'HS256');
          return $token;
     }

     public function verifyToken($header)
     {
          $token = null;
          // extract the token from the header
          if (!empty($header)) {
               if (preg_match('/Bearer\s(\S+)/', $header, $matches)) {
                    $token = $matches[1];
               }
          }

          // check if token is null or empty
          if (is_null($token) || empty($token)) {
               return (object)['state' => false, 'msg' => 'Access denied', 'decoded' => []];
          }

          try {
               $decoded = JWT::decode($token, new Key(PRIVATE_KEY, 'HS256'));
               return (object)['state' => true, 'msg' => 'OK', 'decoded' => $decoded];
          } catch (\Exception $e) {
               return (object)['state' => false, 'msg' => $e->getMessage(), 'decoded' => []];
          }
     }
}
