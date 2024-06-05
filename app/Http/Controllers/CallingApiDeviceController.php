<?php

namespace App\Http\Controllers;

use App\Http\Libraries\JWT\JWTUtils;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\QR;
use App\Models\HomePage;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class CallingApiDeviceController extends Controller
{
    private $jwtUtils;
    public function __construct()
    {
        $this->jwtUtils = new JWTUtils();
    }

    // Decision to select template to show on decvice
    function calling(Request $request)
    {
        try{

            $validator = Validator::make(
                $request->all(),
                [
                    'serial_no' => 'required|string|min:1|max:20',
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

            // device request by sendind serial number. we need to send seat information to it
            $serialNo = $request->serial_no;

            \date_default_timezone_set('Asia/Bangkok');
            $now = new \DateTime();

            // $now = Carbon::parse('2024-01-24 10:30:00');

            // select seat information from Qr code
            // $qr = QR::select('arr.item_object as nameplate_info', 'arr.position')
            //     ->crossJoin(DB::raw("jsonb_array_elements(seat_information) with ordinality as arr(item_object, position)"))
            //     ->where('arr.item_object->serial_no', $serialNo)
            //     ->where(function ($query) use ($now){
            //         $query->where('show_start_at', '<=', $now)
            //             ->where('show_expire_at', '>=', $now);})
            //     ->orderby('created_at','DESC')->limit(1)
            //     ->get();

            $qr = QR::whereJsonContains('seat_information->serial_no', "$serialNo")
                ->where('show_start_at', '<=', $now)
                ->where('show_expire_at', '>=', $now)
                ->orderby('created_at','DESC')->limit(1)
                ->get();

            // return response() -> json($qr);

            // If there is data from QR code, send data from QR code
            if (count($qr) != 0) {
                $attendanceInfo = json_decode($qr[0]->seat_information);
                $templateAdmin = json_decode($qr[0]->template);
                $room = ['room_id' => $qr[0] -> room_id];
                // $templateInfo = json_decode($templateDetail[0]->template_parameter);
                $conCat = (object)array_merge((array)$attendanceInfo, (array)$templateAdmin, (array)$room);

                return response() -> json([
                    "status" => "success",
                    "message" => "using data from QR code",
                    "data" => $conCat,
                ]);
            }
            // if ther is no data from QR code, query data from admin
            else{
                $admin = HomePage::select('arr.item_object as nameplate_info', 'arr.position','template', 'room_id')
                    ->crossJoin(DB::raw("jsonb_array_elements(seat_information) WITH ORDINALITY arr(item_object, position)"))
                    ->whereRaw("(meet_start_at - ?::timestamp) <= '00:10:00'::interval", [$now])
                    ->whereRaw("?::timestamp <= meet_end_at", [$now])
                    ->whereRaw("arr.item_object->>'serial_no' = '$serialNo'")
                    ->get();

                // return response() -> json($admin);

                // If there is data from admin, send data from admin
                if (count($admin) != 0){
                    $attendanceInfo = json_decode($admin[0]->nameplate_info);
                    $templateAdmin = json_decode($admin[0]->template);
                    $room = ["room_id"=>$admin[0]->room_id];
                    // $templateInfo = json_decode($templateDetail[0]->template_parameter);
                    $conCat = (object)array_merge((array)$attendanceInfo, (array)$templateAdmin, (array)$room);

                    return response() -> json([
                        "status" => "success",
                        "message" => "using data from admin",
                        "data" => $conCat,
                    ]);
                }
                // if there is no data any more
                else{
                    return response() -> json([
                    "status" => "success",
                    "message" => "no meeting",
                    "data" => []
                ]);}
            }

        }catch(\Exception $e){
            return response() -> json([
                "status" => "error",
                "message" => $e->getMessage(),
                "data" => []
            ], 500);
        }
    }
}
