<?php

namespace App\Http\Controllers;

use App\Http\Libraries\JWT\JWTUtils;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Cache;
use App\Models\Device;
use App\Models\DeviceSelection;
use App\Models\BatteryLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;

class DevicesController extends Controller
{
    private $jwtUtils;
    public function __construct()
    {
        $this->jwtUtils = new JWTUtils();
    }

    //Register new device into the systenm
    function addDevice(Request $request)
    {
        try {
            $header = $request->header('Authorization');
            $jwt = $this->jwtUtils->verifyToken($header);
            if (!$jwt->state) return response()->json([
                "status" => "error",
                "message" => "Unauthorized",
                "data" => [],
            ], 401);

            $validator = Validator::make(
                $request->all(),
                [
                    'device_name' => 'required|string|min:1|max:255',
                    'serial_no' => 'required|string|min:1|max:20',
                    'ip_address' => 'required|string|min:7|max:15',
                    'mac_address' => 'required|string|min:1|max:255',
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

            $deviceName = $request->device_name;
            $serialNo = $request->serial_no;
            $ipAddress = $request->ip_address;
            $macAddress = $request->mac_address;

            // prevent dubplicated device name
            $deviceList = Device::select('device_name')->where('device_name', $deviceName)->get();
            if (count($deviceList) != 0) {
                return response()->json([
                    "status" => 'error',
                    'message' => 'this device has been in the system',
                    'data' => []
                ]);
            }

            // prevent dubplicated serial_no
            $snList = Device::select('serial_no')->where('serial_no', $serialNo)->get();
            if (count($snList) != 0) {
                return response()->json([
                    "status" => 'error',
                    'message' => 'this serial number has been in the system',
                    'data' => []
                ]);
            }

            $result = Device::insert([
                "device_name" => $deviceName,
                "serial_no" => $serialNo,
                "ip_address" => $ipAddress,
                "mac_address" => $macAddress,
                "battery" => 0
            ]);

            return response()->json([
                "status" => 'success',
                "message" => "Added device successfully",
                "data" => [
                    [
                        "result" => $result
                    ]
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                "status" => "error",
                "message" => $e->getMessage(),
                "data" => [],
            ], 500);
        }
    }

    //change device status manually by admin
    function editStatus(Request $request)
    {
        try {
            $header = $request->header('Authorization');
            $jwt = $this->jwtUtils->verifyToken($header);
            if (!$jwt->state) return response()->json([
                "status" => "error",
                "message" => "Unauthorized",
                "data" => [],
            ], 401);

            $validator = Validator::make(
                $request->all(),
                [
                    'serial_no' => 'required|string|min:1|max:20',
                    'status' => 'required|string|min:1|max:100'
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

            $serialNo = $request->serial_no;
            $status = $request->status;
            $remarks = $request->remarks;

            // check status type
            $statusType = ['available', 'unavailable', 'broken'];
            if (!in_array($status, $statusType)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Wrong status!',
                    'data' => [],
                ]);
            }

            // check device
            $deviceAll = [];
            $decvice = Device::select('serial_no')->get();
            foreach ($decvice as $value) \array_push($deviceAll, $value->serial_no);
            if (!in_array($serialNo, $deviceAll)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'There is no this serial number in the system!',
                    'data' => [],
                ]);
            }

            // if it's broken, need to change device name
            if ($status === 'broken') {
                $deviceName = Device::select('device_name')->where('serial_no', $serialNo)->take(1)->value('device_name');
                $newName = $deviceName . ' ' . now()->format('Y-m-d');
                $result = Device::where('serial_no', $serialNo)->update([
                    'status' => $status,
                    'remarks' => $remarks,
                    'device_name' => $newName
                ]);
            }

            $result = Device::where('serial_no', $serialNo)->update([
                'status' => $status,
                'remarks' => $remarks,
            ]);


            return response()->json([
                "status" => 'success',
                "message" => "Edited device status successfully",
                "data" => [
                    [
                        "result" => $result
                    ]
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                "status" => "error",
                "message" => $e->getMessage(),
                "data" => [],
            ], 500);
        }
    }

    //Edit existing device name instead of broken device
    function editDevice(Request $request)
    {
        try {
            $header = $request->header('Authorization');
            $jwt = $this->jwtUtils->verifyToken($header);
            if (!$jwt->state) return response()->json([
                "status" => "error",
                "message" => "Unauthorized",
                "data" => [],
            ], 401);

            $validator = Validator::make(
                $request->all(),
                [
                    'serial_no'     => 'required|string|min:1|max:255',
                    'device_name'   => 'required|string|min:1|max:255',
                ]
            );

            $serialNo = $request->serial_no;
            $deviceName = $request->device_name;

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

            // check device
            $deviceAll = [];
            $decvice = Device::select('serial_no')->get();
            foreach ($decvice as $value) \array_push($deviceAll, $value->serial_no);
            if (!in_array($serialNo, $deviceAll)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'There is no this serial number in the system!',
                    'data' => [],
                ]);
            }

            // prevent dubplicated device name
            $deviceList = Device::select('device_name')->where('device_name', $deviceName)->get();
            if (count($deviceList) != 0) {
                return response()->json([
                    "status" => 'error',
                    'message' => 'this device has been in the system',
                    'data' => []
                ]);
            }

            $result = Device::where('serial_no', $serialNo)->update([
                'device_name'   => $deviceName //enter new device name
            ]);

            return response()->json([
                "status" => 'success',
                "message" => "Edited device successfully",
                "data" => [
                    [
                        "result" => $result
                    ]
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                "status" => "error",
                "message" => $e->getMessage(),
                "data" => [],
            ], 500);
        }
    }

    // get device to front-end
    function getDevice(Request $request)
    {
        try {
            $header = $request->header('Authorization');
            $jwt = $this->jwtUtils->verifyToken($header);
            if (!$jwt->state) return response()->json([
                "status" => "error",
                "message" => "Unauthorized",
                "data" => [],
            ], 401);

            $cacheKey = "/iNPM/devices-all";
            $cacheData = Cache::get($cacheKey);
            if (!is_null($cacheData)) return response()->json([
                "status" => "success",
                "message" => "Data from cached",
                "data" => json_decode($cacheData),
            ]);

            $result = Device::select(
                'serial_no',
                'device_name',
                'status',
                'is_active',
                'ip_address',
                'mac_address',
                'battery',
                'remarks',
                'created_at'
            )->get();
            Cache::put($cacheKey, json_encode($result), \DateInterval::createFromDateString('5 seconds'));

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

    // active device
    function activeDevice(Request $request)
    {
        try {

            // $validator = Validator::make(
            //     $request->all(),
            //     [
            //         'serial_no'     => 'required|array',
            //     ]
            // );


            // if ($validator->fails()) {
            //     return response()->json([
            //         "status" => "error",
            //         "message" => "Bad request",
            //         "data" => [
            //             [
            //                 "validator" => $validator->errors()
            //             ]
            //         ]
            //     ], 400);
            // }

            $serialNo = $request->serial_no;

            $result = Device::whereNotIn('serial_no', $serialNo)->update([
                'is_active' => false,
            ]);

            return response()->json([
                "status" => 'success',
                "message" => "Device is actived",
                "data" => $result
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                "status" => "error",
                "message" => $e->getMessage(),
                "data" => [],
            ], 500);
        }
    }

    //get available device
    function getFreeDevice(Request $request)
    {
        try {
            $header = $request->header('Authorization');
            $jwt = $this->jwtUtils->verifyToken($header);
            if (!$jwt->state) return response()->json([
                "status" => "error",
                "message" => "Unauthorized",
                "data" => [],
            ], 401);

            $validator = Validator::make(
                $request->all(),
                [
                    'book_id' => 'required|int'
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

            $bookID = $request->book_id;
            $url = 'https://snc-services.sncformer.com/imrs/api-v3/public/index.php/api/get-book-history';
            $response = Http::withoutVerifying()->get($url);
            $data = $response->json();
            $collection = collect($data);
            $filteredData = $collection->where('BookID', $bookID);
            if (count($filteredData) == 0) {
                return response()->json([
                    "status" => 'error',
                    "message" => 'There is no meeting yet'
                ]);
            }

            foreach ($filteredData as $value) {
                $start = $value['StartDatetime'];
                $end = $value['EndDatetime'];
            }

            // query all devices
            $allDeviceQ = Device::select('serial_no', 'device_name')->where('status', 'available')->get();
            $allDevice = [];
            foreach ($allDeviceQ as $value) {
                $allDevice[] = $value->serial_no;
            };

            // query booked deivces
            \date_default_timezone_set('Asia/Bangkok');
            $now = new \DateTime();
            $bookedDeviceQ = DeviceSelection::select('serial_no')
                ->where('meet_start_at', '>=', $start)
                ->where('meet_end_at', '<=', $end)
                ->where('meet_end_at', '>=', $now)
                ->get();
            $bookedDevice = [];
            foreach ($bookedDeviceQ as $value) {
                foreach (json_decode($value->serial_no) as $SN) {
                    $bookedDevice[] = $SN;
                };
            };

            // availabel devices
            $avialableDevicediff = array_diff($allDevice, $bookedDevice);
            $avialableDevice = array_values($avialableDevicediff);

            $device = Device::whereIn('serial_no', $avialableDevice)->select('device_name')->orderBy('created_at', 'asc')->get();

            $deviceArray = [];
            foreach ($device as $value) {
                $deviceArray[] = $value->device_name;
            };

            return response()->json([
                "status" => 'success',
                "message" => "These are avialable devices for this period",
                "data" => $deviceArray
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                "status" => "error",
                "message" => $e->getMessage(),
                "data" => [],
            ], 500);
        }
    }

    //get signal from device by api and add it into database
    function getSignalAPI(Request $request)
    {
        try {
            // need start and end time
            $validator = Validator::make(
                $request->all(),
                [
                    'battery' => 'required|int',
                    "serial_no" => 'required|string|min:1|max:20',
                    "ip_address" => 'required|string|min:1|max:15',
                    "mac_address" => 'required|string|min:1|max:20',
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

            $battery = $request->battery;
            $serialNo = $request->serial_no;

            \date_default_timezone_set('Asia/Bangkok');
            $now = new \DateTime();

            // insert details
            // $logBatt = BatteryLog::insert([
            //     "serial_no" => $serialNo,
            //     "battery" => $battery,
            //     "updated_at" => $now,
            // ]);

            // update details
            $update = Device::where('serial_no', $serialNo)->update([
                "is_active" => true,
                "battery" => $battery,
                "active_at" => $now,
            ]);

            return response()->json([
                "status" => 'success',
                "message" => "receive signal successfully",
                "data" => [
                    [
                        // "add to battery logs" => $logBatt,
                        "update device table" => $update,
                    ]
                ],
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
