<?php

namespace App\Http\Controllers;

use App\Http\Libraries\JWT\JWTUtils;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use App\Models\QR;
use App\Models\DeviceSelection;
use App\Models\HomePage;
use Carbon\Carbon;

class QRController extends Controller
{
    private $jwtUtils;
    public function __construct()
    {
        $this->jwtUtils = new JWTUtils();
    }

    private function randomName(int $length = 5)
    {
        $alphabet = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890-_';
        $pass = array(); //remember to declare $pass as an array
        $alphaLength = strlen($alphabet) - 1; //put the length -1 in cache
        for ($i = 0; $i < $length; $i++) {
            $n = rand(0, $alphaLength);
            $pass[] = $alphabet[$n];
        }
        return \implode($pass); //turn the array into a string
    }

    // Login QR code
    function loginQR(Request $request)
    {
        try{
            $validator = Validator::make(
                $request->all(),
                [
                    "employee_id" => "required|string|min:1|max:10"
                ]
            );

            if($validator->fails()){
                return response()->json([
                    "status" => "error",
                    "message" => "Bad request",
                    "data" => [
                        [
                            "validator" => $validator->error()
                        ]
                    ]
                        ], 400);
            }

            $validated = (object)$validator->validated();
            $employee_id = $validated->employee_id;

            // check with snc oneway api
            $url = 'https://snc-services.sncformer.com/dev/snc-one-way/v2/public/index.php/api/employee/sign-in';
            $data = ['emp_id' => $employee_id];
            $response = Http::withoutVerifying()->post($url, $data);
            $responseData = $response->json();

            // return response()->json($responseData);

            if ($responseData['status'] != "success"){
                return response()->json([
                    "status" => 'error',
                    "message" => 'employee_id does not exist',
                    "data" => [],
                ]);
            }

            \date_default_timezone_set('Asia/Bangkok');
            $dt = new \DateTime();
            $payload = array(
                "username" => $employee_id,
                "iat" => $dt->getTimestamp(),
                'exp' => $dt->modify('+1hours')->getTimestamp(),
            );
            $token = $this->jwtUtils->generateToken($payload);

            return response() -> json([
                "status" => "success",
                "message" => "login success",
                "data" => [
                    [
                        "employee_id" => $employee_id,
                        "token" => $token

                    ]
                ]
            ]);
        }catch(\Exception $e){
            return response() -> json([
                "status" => "error",
                "message" => $e->getMessage(),
                "data" => []
            ], 500);
        }
    }


    // Get seat device information when add information via QR code
    function addInfobyQR(Request $request)
    {
        try {
            $header = $request->header('Authorization');
            $jwt = $this->jwtUtils->verifyToken($header);
            if (!$jwt->state) return response() -> json([
                "status" => "error",
                "message" => "Unauthorized",
                "data" => [],
            ], 401);

            $decoded = $jwt->decoded;

            \date_default_timezone_set('Asia/Bangkok');
            $now = new \DateTime();
            // $now = Carbon::parse('2024-01-24 10:30:00');

            $validator = Validator::make(
                $request->all(),
                [
                    'seat_information' => 'required|array',
                    'show_expire_at' => 'required|string|min:1|max:20',
                    "room_id" => "required|int"
                ]
            );

            if ($validator->fails()) {
                return response()->json([
                    "status" => "error",
                    "message" => "Bad request",
                    "data" => [
                        [
                            "validator" => $validator->errors()
                        ]
                    ]
                ], 400);
            }

            $seatInformation = json_encode($request -> seat_information);
            $showExpire = $request -> show_expire_at;
            $roomID = $request -> room_id;

            // create folder
            $path = getcwd()."\\..\\..\\images\\";
            if(!is_dir($path)) mkdir($path,0777,true);
            // $pathUsed = 'http://10.1.9.77/iNPM-laravel/images/'; // local
            $pathUsed = 'https://snc-services.sncformer.com/dev/inpm/api/images/'; // server
            $fileName = $this->randomName(5) . time() . ".png";

            // save image when font send base64 and not null
            if (json_decode($seatInformation)->logo != null && str_starts_with(json_decode($seatInformation)->logo, 'data:image')){
                // save image
                $folderPath = $path  . "\\";
                if (!is_dir($folderPath)) mkdir($folderPath, 0777, true);
                file_put_contents($folderPath.$fileName,base64_decode(preg_replace('#^data:image/\w+;base64,#i', '',
                                json_decode($seatInformation)->logo)));
                $imagePath = $pathUsed.$fileName;
            }else{
                $imagePath = json_decode($seatInformation)->logo;
            }

            $newSeatInformation = [];
            $newSeatInformation = ["name"=>json_decode($seatInformation)->name, "company"=>json_decode($seatInformation)->company,
                                    "position"=>json_decode($seatInformation)->position,
                                    "serial_no"=>json_decode($seatInformation)->serial_no,
                                    "logo"=>$imagePath];

            $meetingTemplate = HomePage::where('room_id',$roomID)
                                ->where('meet_start_at' ,'<=', $now)
                                ->where('meet_end_at', '>=', $now)
                                ->select('template')->get();

            if (count($meetingTemplate) == 0) {
                $template = ["template_id"=>json_decode($seatInformation)->template_id];
            }else{
                $template = json_decode($meetingTemplate[0]->template);
            }

            // return response()->json($newSeatInformation);

            $result = QR::insert([
                "employee_id" => $decoded->username,
                "seat_information" => json_encode($newSeatInformation),
                "template" => json_encode($template),
                "created_at" => $now,
                "show_start_at" => $now,
                "show_expire_at" => $showExpire,
                "room_id" => $roomID,
            ]);

            // Add into device selection table
            $serialNo = json_decode($seatInformation)->serial_no;
            $addSN = DeviceSelection::insert([
                "book_id" => 99,
                "serial_no" =>json_encode([$serialNo]),
                "meet_start_at" => $now,
                "meet_end_at" => $showExpire,
                "room_id" => $roomID
            ]);

            return response()->json([
                "status" => 'success',
                "message" => "record from QR code successfully",
                "data" => [
                    [
                        "result" => $result
                    ]
                ],
            ]);


        } catch(\Exception $e){
            return response() -> json([
                "status" => "error",
                "message" => $e->getMessage(),
                "data" => []
            ], 500);
        }
    }

    // API tto get seat information from QR code
    function getSeatQR(Request $request)
    {
        try {
            $result = QR::select('*')->get();

            return response()->json([
                "status" => 'success',
                "message" => "Getting device successfully",
                "data" => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                "status" => "error",
                "message" => $e->getMessage(),
                "data" => [],
            ], 500);
        }
    }
}
