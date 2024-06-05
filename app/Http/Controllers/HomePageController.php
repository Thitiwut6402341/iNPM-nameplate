<?php

namespace App\Http\Controllers;

use App\Http\Libraries\JWT\JWTUtils;
use App\Models\Device;
use App\Models\DeviceSelection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use App\Models\HomePage;
use App\Models\SncEmployees;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class HomePageController extends Controller
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

    // send  meeting information for present and future meeting
    public function presentMeeting(Request $request)
    {
        try {
            $header = $request->header('Authorization');
            $jwt = $this->jwtUtils->verifyToken($header);
            if (!$jwt->state) return response()->json([
                "status" => "error",
                "message" => "Unauthorized",
                "data" => [],
            ], 401);

            $cacheKey = "/iNPM/present-meeting-information";
            $cacheData = Cache::get($cacheKey);
            if (!is_null($cacheData)) return response()->json([
                "status" => "success",
                "message" => "Data from cached",
                "data" => json_decode($cacheData),
            ]);

            \date_default_timezone_set('Asia/Bangkok');
            $now = new \DateTime();

            // pull book id from iMRS api
            $url = 'https://snc-services.sncformer.com/imrs/api-v3/public/index.php/api/book/get-book-history';
            $response = Http::withoutVerifying()->get($url);
            $data = $response->json();
            $collection = collect($data);
            // Select only vip approved room
            $filteredData = $collection->where('RoomLevel', 'vip')->where('Status', 'approved');
            // $filteredData = $collection->where('Status', 'approved');

            if (!$filteredData)  return response()->json([
                "status" => 'error',
                "message" => "connot pull data from API",
                "data" => [],
            ]);

            $bookingData = [];
            foreach ($filteredData as $value) {
                if (Carbon::parse($value['EndDatetime']) >= $now) {
                    $bookingData[] = [
                        "bookID" => $value['BookID'], "roomID" => $value['RoomID'], "roomName" => $value['RoomName'], "booker" => $value['Name'],
                        "event" => $value['Purpose'], "start" => $value['StartDatetime'], "end" => $value['EndDatetime']
                    ];
                }
            }

            Cache::put($cacheKey, json_encode($bookingData), \DateInterval::createFromDateString('1 minutes'));

            return response()->json([
                "status" => 'success',
                "message" => "send information already",
                "data" =>  $bookingData,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                "status" => "error",
                "message" => $e->getMessage(),
                "data" => [],
            ], 500);
        }
    }

    // send  meeting information for history meeting
    public function historyMeeting(Request $request)
    {
        try {
            $header = $request->header('Authorization');
            $jwt = $this->jwtUtils->verifyToken($header);
            if (!$jwt->state) return response()->json([
                "status" => "error",
                "message" => "Unauthorized",
                "data" => [],
            ], 401);

            $cacheKey = "/iNPM/history-meeting-information";
            $cacheData = Cache::get($cacheKey);
            if (!is_null($cacheData)) return response()->json([
                "status" => "success",
                "message" => "Data from cached",
                "data" => json_decode($cacheData),
            ]);

            \date_default_timezone_set('Asia/Bangkok');
            $now = new \DateTime();

            // pull book id from iMRS api
            $url = 'https://snc-services.sncformer.com/imrs/api-v3/public/index.php/api/book/get-book-history';
            $response = Http::withoutVerifying()->get($url);
            $data = $response->json();
            $collection = collect($data);
            // Select only vip approved room
            $filteredData = $collection->where('RoomLevel', 'vip')->where('Status', 'approved');
            // $filteredData = $collection->where('Status', 'approved');

            if (!$filteredData)  return response()->json([
                "status" => 'error',
                "message" => "connot pull data from API",
                "data" => [],
            ]);

            $bookingData = [];
            foreach ($filteredData as $value) {
                if (Carbon::parse($value['EndDatetime']) < $now) {
                    $bookingData[] = [
                        "bookID" => $value['BookID'], "roomID" => $value['RoomID'], "roomName" => $value['RoomName'], "booker" => $value['Name'],
                        "event" => $value['Purpose'], "start" => $value['StartDatetime'], "end" => $value['EndDatetime']
                    ];
                }
            }

            Cache::put($cacheKey, json_encode($bookingData), \DateInterval::createFromDateString('1 minutes'));

            return response()->json([
                "status" => 'success',
                "message" => "send information already",
                "data" =>  $bookingData,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                "status" => "error",
                "message" => $e->getMessage(),
                "data" => [],
            ], 500);
        }
    }

    // add attendance information and add more additional seat (and delete and change info)
    function receiveNameplateInfo(Request $request)
    {
        try {
            $header = $request->header('Authorization');
            $jwt = $this->jwtUtils->verifyToken($header);
            if (!$jwt->state) return response()->json([
                "status" => "error",
                "message" => "Unauthorized",
                "data" => [],
            ], 401);

            \date_default_timezone_set('Asia/Bangkok');
            $now = new \DateTime();

            $validators = Validator::make(
                $request->all(),
                [
                    'book_id' => 'required|int',
                    'seat_information' => 'required|array',
                    'template' => 'required|array'
                ]
            );

            if ($validators->fails()) {
                return response()->json([
                    "status" => "error",
                    "message" => "Bad request",
                    "data" => [
                        [
                            "validator" => $validators->errors()
                        ]
                    ]
                ], 400);
            }

            //get information from iMRS api
            $url = 'https://snc-services.sncformer.com/imrs/api-v3/public/index.php/api/book/get-book-history';
            $response = Http::withoutVerifying()->get($url);
            $data = $response->json();
            $collection = collect($data);

            $bookId = $request->book_id;
            $seatInformation = json_encode($request->seat_information);
            $template = $request->template;
            // return response()->json($template);

            $filteredData = $collection->where('BookID', $bookId);
            foreach ($filteredData as $info) {
                $roomID = $info['RoomID'];
                $roomName = $info['RoomName'];
                $bookerName = $info['Name'];
                $event = $info['Purpose'];
                $start = Carbon::parse($info['StartDatetime']);
                $end = Carbon::parse($info['EndDatetime']);
            }

            // create folder for storing image
            $path = getcwd() . "\\..\\..\\images\\";
            if (!is_dir($path)) mkdir($path, 0777, true);

            // $pathUsed = 'http://10.1.9.77/iNPM-laravel/images/'; // local
            $pathUsed = 'https://snc-services.sncformer.com/dev/inpm/api/images/'; // server

            // query for checking seat management
            $checkData = HomePage::select('*')->where('book_id', $bookId)->get();

            // need to map device name to serial number
            $seatInformationNew = [];
            foreach (json_decode($seatInformation) as $value) {

                $fileName = $this->randomName(5) . time() . ".png";
                $serialNumberMapping = Device::where('device_name', $value->device_name)->select('serial_no')->get();

                // save image when sending base64 and not null
                if ($value->logo != null && str_starts_with($value->logo, 'data:image')) {
                    // return response()->json("is base64");
                    // save image
                    $folderPath = $path  . "\\";
                    if (!is_dir($folderPath)) mkdir($folderPath, 0777, true);
                    file_put_contents($folderPath . $fileName, base64_decode(preg_replace(
                        '#^data:image/\w+;base64,#i',
                        '',
                        $value->logo
                    )));
                    $imagePath = $pathUsed . $fileName;
                } else {
                    // return response()->json("is not base64");
                    $imagePath = $value->logo;
                }

                //new data
                //if send only name company and position
                if ($value->seat_no === null || $value->device_name === null) {
                    $seatInformationNew[] = [
                        "name" => $value->name, "position" => $value->position, "company" => $value->company,
                        "logo" => "", "seat_no" => $value->seat_no, "serial_no" => "",
                        "device_name" => ""
                    ];
                } else { // send all
                    $seatInformationNew[] = [
                        "name" => $value->name, "position" => $value->position, "company" => $value->company,
                        "logo" => $imagePath, "seat_no" => $value->seat_no, "serial_no" => $serialNumberMapping[0]->serial_no,
                        "device_name" => $value->device_name
                    ];
                }
            }

            $logoDefault = $template["logoDefault"];
            // return response()->json($logoDefault);
            if ($logoDefault != null) {
                // return response()->json("logo 2");
                $fileName = $this->randomName(5) . time() . ".png";
                $folderPath = $path  . "\\";
                if (!is_dir($folderPath)) mkdir($folderPath, 0777, true);
                file_put_contents($folderPath . $fileName, base64_decode(preg_replace(
                    '#^data:image/\w+;base64,#i',
                    '',
                    $logoDefault
                )));
                $imagePath = $pathUsed . $fileName;

                $template["logoDefault"] = $imagePath;
                // return response()->json($template);
            }


            // get serial number that were booked
            $serialNo = [];
            foreach ($seatInformationNew as $value) {
                if ($value['serial_no'] != "") {
                    $serialNo[] = $value['serial_no'];
                }
            }

            // If there is no information in that book id
            if (count($checkData) == 0) {
                // Insert data to meeting information
                $result = HomePage::insert([
                    "book_id" => $bookId,
                    "room_id" => $roomID,
                    "room_name" => $roomName,
                    "booker_name" => $bookerName,
                    "event" => $event,
                    "meet_start_at" => $start,
                    "meet_end_at" => $end,
                    "seat_information" => json_encode($seatInformationNew),
                    "template" => json_encode($template),

                ]);

                // Insert devices to device selection
                $addSN = DeviceSelection::insert([
                    "book_id" => $bookId,
                    "serial_no" => json_encode($serialNo),
                    "meet_start_at" => $start,
                    "meet_end_at" => $end,
                    "room_id" => $roomID,
                ]);

                return response()->json([
                    "status" => 'success',
                    "message" => "add seat successfully",
                    "data" => [
                        [
                            "result" => $result
                        ]
                    ],
                ]);
            }

            // If there is information in that book id
            else {
                // update all by overwrite it
                $result = HomePage::where('book_id', $bookId)->update([
                    "seat_information" => $seatInformationNew,
                    "template" => $template,

                ]);

                $addSN = DeviceSelection::where('book_id', $bookId)->update([
                    "serial_no" => json_encode($serialNo),
                ]);

                return response()->json([
                    "status" => 'success',
                    "message" => "add seat successfully",
                    "data" => [
                        [
                            "result" => $result
                        ]
                    ],
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                "status" => "error",
                "message" => $e->getMessage(),
                "data" => [],
            ], 500);
        }
    }

    // API for seat information
    function getSeatInfo(Request $request)
    {
        try {
            $header = $request->header('Authorization');
            $jwt = $this->jwtUtils->verifyToken($header);
            if (!$jwt->state) return response()->json([
                "status" => "error",
                "message" => "Unauthorized",
                "data" => [],
            ], 401);

            $validators = Validator::make(
                $request->all(),
                [
                    'book_id' => 'required|int',
                ]
            );

            if ($validators->fails()) {
                return response()->json([
                    "status" => "error",
                    "message" => "Bad request",
                    "data" => [
                        [
                            "validator" => $validators->errors()
                        ]
                    ]
                ], 400);
            }

            $bookID = $request->book_id;

            $result = HomePage::select('*')->where('book_id', $bookID)->get();
            foreach ($result as $value) {
                $value->seat_information = json_decode($value->seat_information);
                $value->template = json_decode($value->template);
            }

            // if they don't manage meeting yet
            if (count($result) == 0) {
                $url = 'https://snc-services.sncformer.com/imrs/api-v3/public/index.php/api/book/get-book-history';
                $response = Http::withoutVerifying()->get($url);
                $data = $response->json();
                $collection = collect($data);
                // Select only vip approved room
                $filteredData = $collection->where('BookID', $bookID);

                $bookDetail = [];
                foreach ($filteredData as $value) {
                    $bookDetail = [
                        "book_id" => $value['BookID'], "room_id" => $value['RoomID'], "room_name" => $value['RoomName'], "booker_name" => $value['Name'],
                        "event" => $value['Purpose'], "meet_start_at" => $value['StartDatetime'], "meet_end_at" => $value['EndDatetime'],
                        "seat_information" => [], "template" => []
                    ];
                }

                return response()->json([
                    "status" => 'success',
                    "message" => "Don't manage seat yet",
                    "data" => $bookDetail
                ]);
            }

            return response()->json([
                "status" => 'success',
                "message" => "get data successfully",
                "data" => $result[0]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                "status" => "error",
                "message" => $e->getMessage(),
                "data" => [],
            ], 500);
        }
    }

    // Get snc employees for P'pong's table
    function getSNCEmployees(Request $request)
    {
        try {

            $cacheKey = "/iNPM/get-snc-employee";
            $cacheData = Cache::get($cacheKey);
            if (!is_null($cacheData)) return response()->json([
                "status" => "success",
                "message" => "Data from cached",
                "data" => json_decode($cacheData),
            ]);

            $sncEmployees = array();
            // Thai name
            $sncThai = SncEmployees::select(
                DB::raw("CONCAT(tb_snc_employees.first_name_th,' ',tb_snc_employees.last_name_th) as name"),
                'tb_snc_employees.position_name as position',
                'tb_snc_employees.company_id',
                'tb_snc_employees.company_name',
                'tb_company_logo.logo',
            )
                ->join('tb_company_logo', 'tb_snc_employees.company_id', '=', 'tb_company_logo.company_abbr')
                ->get();

            //English name
            $sncEng = SncEmployees::select(
                DB::raw("CONCAT(tb_snc_employees.first_name_en,' ',tb_snc_employees.last_name_en) as name"),
                DB::raw("CASE
                                                    WHEN tb_snc_employees.position_name = 'PRODUCTION CONTROL SUPERVISOR' THEN 'PRODUCTION CONTROL SUPERVISOR'
                                                    WHEN tb_snc_employees.position_name = '8' THEN '8'
                                                    WHEN tb_snc_employees.position_name = 'QUALITY MANAGER' THEN 'QUALITY MANAGER'
                                                    WHEN tb_snc_employees.position_name = 'แม่บ้าน' THEN 'Maid'
                                                    WHEN tb_snc_employees.position_name = 'EQUIPMENT SUPERVISOR' THEN 'EQUIPMENT SUPERVISOR'
                                                    WHEN tb_snc_employees.position_name = 'QUALITY CONTROL SUPERVISOR' THEN 'QUALITY CONTROL SUPERVISOR'
                                                    WHEN tb_snc_employees.position_name = 'Production engineer' THEN 'Production engineer'
                                                    WHEN tb_snc_employees.position_name = 'ประธานกรรมการบริหาร' THEN 'Executive Chairman'
                                                    WHEN tb_snc_employees.position_name = '-' THEN '-'
                                                    WHEN tb_snc_employees.position_name = 'ผู้ช่วยผู้จัดการ' THEN 'Assistant Manager'
                                                    WHEN tb_snc_employees.position_name = 'หัวหน้างาน' THEN 'Devision Chief'
                                                    WHEN tb_snc_employees.position_name = 'ENGINEER' THEN 'ENGINEER'
                                                    WHEN tb_snc_employees.position_name = 'ที่ปรึกษา' THEN 'Advisor'
                                                    WHEN tb_snc_employees.position_name = 'IN PROCESS QUALITY CONTROL SUPERVISOR (AIRN CONDITIONER)' THEN 'IN PROCESS QUALITY CONTROL SUPERVISOR (AIRN CONDITIONER)'
                                                    WHEN tb_snc_employees.position_name = 'กรรมการผู้จัดการ' THEN 'Managr Director'
                                                    WHEN tb_snc_employees.position_name = 'ผู้ช่วยกรรมการผู้จัดการ' THEN 'Assistant Managr Director'
                                                    WHEN tb_snc_employees.position_name = 'ผู้จัดการแผนก' THEN 'Department Managr'
                                                    WHEN tb_snc_employees.position_name = 'Electronic Control Supervisor' THEN 'Electronic Control Supervisor'
                                                    WHEN tb_snc_employees.position_name = 'พนักงานสัญญาจ้าง' THEN 'Contract Employee'
                                                    WHEN tb_snc_employees.position_name = 'PRODUCTION CONTROL DIRECTOR' THEN 'PRODUCTION CONTROL DIRECTOR'
                                                    WHEN tb_snc_employees.position_name = 'MOLD SUPERVISOR' THEN 'MOLD SUPERVISOR'
                                                    WHEN tb_snc_employees.position_name = 'Executive Secretary' THEN 'Executive Secretary'
                                                    WHEN tb_snc_employees.position_name = 'LEADER' THEN 'LEADER'
                                                    WHEN tb_snc_employees.position_name = 'นักศึกษาฝึกงาน' THEN 'Intrenship Student'
                                                    WHEN tb_snc_employees.position_name = 'Process Quality Control Supervisor' THEN 'Process Quality Control Supervisor'
                                                    WHEN tb_snc_employees.position_name = 'MATERIALS CONTROL SUPERVISOR' THEN 'MATERIALS CONTROL SUPERVISOR'
                                                    WHEN tb_snc_employees.position_name = 'Mini MD' THEN 'Mini MD'
                                                    WHEN tb_snc_employees.position_name = 'ผู้ช่วยฝ่ายบริหาร' THEN 'Administrative Assistant'
                                                    WHEN tb_snc_employees.position_name = 'กรรมการบริษัท' THEN 'Company Director'
                                                    WHEN tb_snc_employees.position_name = 'Production Tecnician' THEN 'Production Tecnician'
                                                    WHEN tb_snc_employees.position_name = 'รองประธานกรรมการบริหาร' THEN 'Executive Vice Chairman'
                                                    WHEN tb_snc_employees.position_name = 'พ่อบ้าน' THEN 'Butler'
                                                    WHEN tb_snc_employees.position_name = 'Act.Mini MD' THEN 'Act.Mini MD'
                                                    WHEN tb_snc_employees.position_name = 'Mini MD Department' THEN 'Mini MD Department'
                                                    WHEN tb_snc_employees.position_name = 'พนักงาน HISENSE' THEN 'HISENSE EMPLOYEE'
                                                    WHEN tb_snc_employees.position_name = 'Production director' THEN 'Production director'
                                                    WHEN tb_snc_employees.position_name = 'Production supervisor' THEN 'Production supervisor'
                                                    WHEN tb_snc_employees.position_name = 'พนักงาน' THEN 'Employee'
                                                    WHEN tb_snc_employees.position_name = 'MOLD MANAGER' THEN 'MOLD MANAGER'
                                                    WHEN tb_snc_employees.position_name = 'SPECIAL LIST' THEN 'SPECIAL LIST'
                                                    WHEN tb_snc_employees.position_name = 'ผู้จัดการฝ่าย' THEN 'Department Manager'
                                                    WHEN tb_snc_employees.position_name = 'หัวหน้าแผนก' THEN 'Department Head'
                                                    WHEN tb_snc_employees.position_name = 'พนักงานทดลองงาน' THEN 'Probationary Employee'
                                                    WHEN tb_snc_employees.position_name = 'ที่ปรึกษาบริษัท' THEN 'Company Consultant'
                                                    WHEN tb_snc_employees.position_name = 'ผู้อำนวยการหลักสูตร' THEN 'Course Director'
                                                    WHEN tb_snc_employees.position_name = 'ผู้ช่วยประธานกรรมการบริหาร' THEN 'Assistant Chairman of the Executive Committee'
                                                    WHEN tb_snc_employees.position_name = 'Acting Mini MD' THEN 'Acting Mini MD'
                                                END AS position"),
                'tb_snc_employees.company_id',
                'tb_snc_employees.company_name',
                'tb_company_logo.logo'
            )
                ->join('tb_company_logo', 'tb_snc_employees.company_id', '=', 'tb_company_logo.company_abbr')
                ->where('first_name_en', '!=', null)->where('first_name_en', '!=', '')
                ->get();

            foreach ($sncThai as $value) {
                array_push($sncEmployees, $value);
            }
            foreach ($sncEng as $value) {
                array_push($sncEmployees, $value);
            }

            Cache::put($cacheKey, json_encode($sncEmployees), \DateInterval::createFromDateString('10 minutes'));

            return response()->json([
                "status" => 'success',
                "massage" => 'employees list',
                "data" => $sncEmployees
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
