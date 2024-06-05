<?php

namespace App\Http\Controllers;

use App\Http\Libraries\JWT\JWTUtils;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use App\Models\MainCateSecondDP;
use App\Models\SubCateSecondDP;
use App\Models\NotificationSecondDP;
use App\Models\Rising;
use Illuminate\Support\Facades\Cache;


class SecondDisplayController extends Controller
{
    private $jwtUtils;
    public function __construct()
    {
        $this->jwtUtils = new JWTUtils();
    }

// ********************************************************************************************************************************************
    //get data from main category
    function getMainCategories(Request $request)
    {
        try{

            $cacheKey = "/iNPM/get-Main-Category";
            $cacheData = Cache::get($cacheKey);
            if (!is_null($cacheData)) return response()->json([
                "status" => "success",
                "message" => "Data from cached",
                "data" => json_decode($cacheData),
            ]);

            $result = MainCateSecondDP::select('*')->orderBy('index')->get();
            foreach($result as $value){
                $value->main_desc = json_decode($value->main_desc);
            }

            Cache::put($cacheKey, json_encode($result), \DateInterval::createFromDateString('1 minutes'));

            return response() -> json([
            "status" => "success",
            "message" => "get mian category successfully!",
            "data" => $result
            ]);

        }catch(\Exception $e){
            return response() -> json([
                "status" => "error",
                "message" => $e->getMessage(),
                "data" => []
            ], 500);
        }
    }

    //add main category
    function addMainCategories(Request $request)
    {
        try{

            $validator = Validator::make(
                $request->all(),
                [
                    'main_desc' => 'required | array',
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

            $mainDESC = $request->main_desc;
            $result = MainCateSecondDP::insert([
                'main_desc' => json_encode($mainDESC)
            ]);

            return response() -> json([
            "status" => "success",
            "message" => "add mian category successfully!",
            "data" => [$result]
            ]);

        }catch(\Exception $e){
            return response() -> json([
                "status" => "error",
                "message" => $e->getMessage(),
                "data" => []
            ], 500);
        }
    }

    //edit main category
    function editMainCategories(Request $request)
    {
        try{

            $validator = Validator::make(
                $request->all(),
                [
                    'main_cate_id' => 'required | uuid',
                    'main_desc' => 'required | array'
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

            $mainCateId = $request -> main_cate_id;
            $mainDESC = $request -> main_desc;

            $result = MainCateSecondDP::where('main_cate_id',$mainCateId)->update([
                'main_desc' => json_encode($mainDESC)
            ]);

            return response() -> json([
            "status" => "success",
            "message" => "edit mian category successfully!",
            "data" => [$result]
            ]);

        }catch(\Exception $e){
            return response() -> json([
                "status" => "error",
                "message" => $e->getMessage(),
                "data" => []
            ], 500);
        }
    }

    // delete main category
    function deleteMainCategories(Request $request)
    {
        try{

            $validator = Validator::make(
                $request->all(),
                [
                    'main_cate_id' => 'required | uuid',
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

            $mainCateId = $request -> main_cate_id;

            $result = MainCateSecondDP::where('main_cate_id',$mainCateId)->delete();

            return response() -> json([
            "status" => "success",
            "message" => "delete mian category successfully!",
            "data" => [$result]
            ]);

        }catch(\Exception $e){
            return response() -> json([
                "status" => "error",
                "message" => $e->getMessage(),
                "data" => []
            ], 500);
        }
    }

// ********************************************************************************************************************************************
    // get sub category
    function getSubCategories(Request $request)
    {
        try{

            $cacheKey = "/iNPM/get-Sub-Category";
            $cacheData = Cache::get($cacheKey);
            if (!is_null($cacheData)) return response()->json([
                "status" => "success",
                "message" => "Data from cached",
                "data" => json_decode($cacheData),
            ]);

            $result = SubCateSecondDP::select('*')->orderBy('main_cate_id')->orderBy('index')->get();
            foreach($result as $value){
                $value->sub_desc = json_decode($value->sub_desc);
            }

            Cache::put($cacheKey, json_encode($result), \DateInterval::createFromDateString('1 minutes'));

            return response() -> json([
            "status" => "success",
            "message" => "get sub category successfully!",
            "data" => $result
            ]);

        }catch(\Exception $e){
            return response() -> json([
                "status" => "error",
                "message" => $e->getMessage(),
                "data" => []
            ], 500);
        }
    }

    // add sub category
    function addSubCategories(Request $request)
    {
        try{

            $validator = Validator::make(
                $request->all(),
                [
                    'main_cate_id' => 'required | uuid',
                    'sub_desc' => 'required | array'
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

            $mainCateId = $request -> main_cate_id;
            $subDESC = $request -> sub_desc;

            $result = SubCateSecondDP::insert([
                'main_cate_id' => $mainCateId,
                'sub_desc' => json_encode($subDESC),
            ]);

            return response() -> json([
            "status" => "success",
            "message" => "add sub category successfully!",
            "data" => [$result]
            ]);

        }catch(\Exception $e){
            return response() -> json([
                "status" => "error",
                "message" => $e->getMessage(),
                "data" => []
            ], 500);
        }
    }

    //edit sub category
    function editSubCategories(Request $request)
    {
        try{

            $validator = Validator::make(
                $request->all(),
                [
                    'sub_cate_id' => 'required | uuid',
                    'sub_desc' => 'required | array'
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

            $subCateId = $request -> sub_cate_id;
            $subDESC = $request -> sub_desc;

            $result = SubCateSecondDP::where('sub_cate_id',$subCateId)->update([
                'sub_desc' => json_encode($subDESC)
            ]);

            return response() -> json([
            "status" => "success",
            "message" => "edit sub category successfully!",
            "data" => [$result]
            ]);

        }catch(\Exception $e){
            return response() -> json([
                "status" => "error",
                "message" => $e->getMessage(),
                "data" => []
            ], 500);
        }
    }

// ************************************************************************************************************************************
    // Notification display 2
    function secondNotification(Request $request){
        try{
            $validator = Validator::make(
                $request->all(),
                [
                    "serial_no" => 'required | string | min:1 | max:20',
                    "sub_cate_id" => 'required | uuid'
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

            $serialNo = $request -> serial_no;
            $subCateId = $request -> sub_cate_id;

            // $url = 'http://10.1.9.77/iNPM-laravel/server/public/index.php/api/iNPM/calling';// local
            $url = 'https://snc-services.sncformer.com/dev/inpm/api/v2/public/index.php/api/iNPM/calling';// server

            $data = ['serial_no' => $serialNo];
            $response = Http::withoutVerifying()->post($url, $data);
            $responseData = $response->json();

            $query = SubCateSecondDP::select('tb_sub_categories_2nd_display.sub_desc', 'tb_main_categories_2nd_display.main_desc')
                -> join('tb_main_categories_2nd_display', 'tb_sub_categories_2nd_display.main_cate_id','=','tb_main_categories_2nd_display.main_cate_id')
                -> where('tb_sub_categories_2nd_display.sub_cate_id',$subCateId)
                -> get();

            foreach ($query as $value){
                $value->sub_desc = json_decode($value->sub_desc);
                $value->main_desc = json_decode($value->main_desc);
            }

            $name = $responseData['data']['name'];
            // $seat = $responseData['data']['seat_no'];
            $mainDESC = $query[0]->main_desc;
            $subDESC = $query[0]->sub_desc;

            $resultInsert = NotificationSecondDP::insert([
                'serial_no' => $serialNo,
                'name' => $name,
                'main_topic' => json_encode($mainDESC),
                'sub_topic' => json_encode($subDESC),
            ]);

            //  if send contact notification
            if ($subDESC->en == "Contact"){
                $validator = Validator::make(
                    $request->all(), ["detail" => 'required | string | min:1 | max:255']
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

                $detail = $request -> detail;
            }else{
                $detail = $subDESC->th;
            }

            if ($subCateId != 'df182fcb-3c13-4dda-a181-3ee8478788ce'){
                $sToken= "0gsG4ciCTCAkBZdg0UScEE8MK1c7JZ8qO0Bm6luCCCG";
                $sMessage = "\n"."📱 "."*".$mainDESC->th."*"."\n\n"."🧑‍🤝‍🧑 คุณ "."*".$name."*"."\n\n"."🎮 หมายเลย nameplate: "."*".$serialNo."*"."\n\n"."✋ ต้องการ "."*".$detail."*";
                $chOne = curl_init();
                curl_setopt( $chOne, CURLOPT_URL, "https://notify-api.line.me/api/notify");
                curl_setopt( $chOne, CURLOPT_SSL_VERIFYHOST, 0);
                curl_setopt( $chOne, CURLOPT_SSL_VERIFYPEER, 0);
                curl_setopt( $chOne, CURLOPT_POST, 1);
                curl_setopt( $chOne, CURLOPT_POSTFIELDS, "message=".$sMessage);
                $headers = array( 'Content-type: application/x-www-form-urlencoded', 'Authorization: Bearer '.$sToken.'', );
                curl_setopt($chOne, CURLOPT_HTTPHEADER, $headers);
                curl_setopt( $chOne, CURLOPT_RETURNTRANSFER, 1);
                $result = curl_exec( $chOne );
                curl_close( $chOne );
            }

            return response() -> json([
                "status" => "success",
                "message" => "get notification successfully",
                "data" => $resultInsert
            ]);

        }catch(\Exception $e){
            return response() -> json([
                "status" => "error",
                "message" => $e->getMessage(),
                "data" => []
            ], 500);
        }
    }

    //get notification
    function getNotification(){
        try{

            $cacheKey = "/iNPM/get-notify-second-display";
            $cacheData = Cache::get($cacheKey);
            if (!is_null($cacheData)) return response()->json([
                "status" => "success",
                "message" => "Data from cached",
                "data" => json_decode($cacheData),
            ]);

            $result = NotificationSecondDP::select('*')->get();
            foreach ($result as $value){
                $value -> main_topic = json_decode($value -> main_topic);
                $value -> sub_topic = json_decode($value -> sub_topic);
            }

            Cache::put($cacheKey, json_encode($result), \DateInterval::createFromDateString('10 seconds'));

            return response() -> json([
                "status" => "success",
                "message" => "notification alert",
                "data" => $result
            ]);
        }catch(\Exception $e){
            return response() -> json([
                "status" => "error",
                "message" => $e->getMessage(),
                "data" => []
            ], 500);
        }
    }

    // get current position
    function currentPosition(Request $request){
        try{
            $validator = Validator::make(
                $request->all(),
                [
                    "serial_no" => 'required | string | min:1 | max:20',
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

            $serialNo = $request -> serial_no;
            // $url = 'http://10.1.9.77/iNPM-laravel/server/public/index.php/api/iNPM/calling';// local
            $url = 'https://snc-services.sncformer.com/dev/inpm/api/v2/public/index.php/api/iNPM/calling';// server

            $cacheKey = "/iNPM/get-notify-second-display"."/".$serialNo;
            $cacheData = Cache::get($cacheKey);
            if (!is_null($cacheData)) return response()->json([
                "status" => "success",
                "message" => "Data from cached",
                "data" => json_decode($cacheData),
            ]);


            $data = ['serial_no' => $serialNo];
            $response = Http::withoutVerifying()->post($url, $data);
            $responseData = $response->json();

            Cache::put($cacheKey, json_encode($responseData['data']), \DateInterval::createFromDateString('10 seconds'));

            return response() -> json([
                "status" => "success",
                "message" => "Attendance information",
                "data" => $responseData['data']
            ]);


        }catch(\Exception $e){
            return response() -> json([
                "status" => "error",
                "message" => $e->getMessage(),
                "data" => []
            ], 500);
        }
    }

    //rise hand api for alert
    function riseHand(Request $request){
        try{
            $result = NotificationSecondDP::Select('name','created_at as timestamp')
            ->whereRaw("main_topic->>'en' = ?",["Rise"])->get();

            return response() -> json([
                "status" => "success",
                "message" => "People are Rise hand",
                "data" => $result
            ]);

        }catch(\Exception $e){
            return response() -> json([
                "status" => "error",
                "message" => $e->getMessage(),
                "data" => []
            ], 500);
        }
    }

    // rise hand api for show at 1st display
    function riseHandShow(Request $request){
        try{
            $validator = Validator::make(
                $request->all(),
                [
                    "serial_no" => 'required | string | min:1 | max:20',
                    "is_rising" => 'required | boolean'
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

            $serialNo = $request -> serial_no;
            $rising = $request -> is_rising;

            $checkRising = Rising::where('serial_no',$serialNo)->get();
            if (count($checkRising) == 0){
                $add = Rising::insert([
                    'serial_no' => $serialNo,
                    'is_rising' => $rising
                ]);
            }else{
                $up = Rising::where('serial_no',$serialNo)->update([
                    'is_rising' => $rising
                ]);
            }

            return response() -> json([
                "status" => "success",
                "message" => "Rise hand successfully",
                "data" => []
            ]);

        }catch(\Exception $e){
            return response() -> json([
                "status" => "error",
                "message" => $e->getMessage(),
                "data" => []
            ], 500);
        }
    }

    // show each device
    function showEach(Request $request){
        try{
            $validator = Validator::make(
                $request->all(),
                [
                    "serial_no" => 'required | string | min:1 | max:20'
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

            $serialNo = $request -> serial_no;
            $result = Rising::where('serial_no',$serialNo)->get();

            if (count($result) == 0){
                return response() -> json([
                    "status" => "success",
                    "message" => "Rise hand status",
                    "data" => [
                        "serial_no"=> $serialNo,
                        "is_rising"=> false
                    ]
                ]);
            }

            return response() -> json([
                "status" => "success",
                "message" => "Rise hand status",
                "data" => $result[0]
            ]);

        }catch(\Exception $e){
            return response() -> json([
                "status" => "error",
                "message" => $e->getMessage(),
                "data" => []
            ], 500);
        }
    }
}
