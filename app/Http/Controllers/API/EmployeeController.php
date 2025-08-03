<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Employee;
use App\Models\Timesheet;
use App\Models\TimeInOutLog;

use Carbon\Carbon;
use App\Models\BioStation;

class EmployeeController extends Controller
{
    //
    public function index(Request $request)
    {
        $employees = Employee::paginate(5);

        return response()->json([
            'status' => 1,
            'message' => 'Employees retrieved successfully',
            'data' => $employees
        ]);
    }

    public function getTimeRecord(Request $request)
    {
        $timeRecords = Timesheet::whereDay('transaction_date', 1)
            ->whereMonth('transaction_date', 7)
            ->whereYear('transaction_date', 2024)
            ->where('employee_id', 128)
            ->get();

        return response()->json([
            'status' => 1,
            'message' => 'Timesheet retrieved successfully',
            'data' => $timeRecords
        ]);
    }

    public function updateOrCreateTimesheet(Request $request)
    {
        // Validate the incoming request
        // $request->validate([
        //     'employeeId' => 'required|integer',
        //     'transactionDate' => 'required|date', 
        // ]);

        // Prepare the data to update or create 
       
        $transactionDate = Carbon::parse($request->transaction_date);
        $timesheet = null;
        $device_name = $request->device_name;
        $type = $request->type;
        $employee_id = $request->employee_id;
        $time = $request->time;


        // Format the day of the week as a three-letter abbreviation

        $employeeTimeSchedule = Employee::find($request->employee_id);
        $employeeDTR = Timesheet::where('employee_id', $request->employee_id)->where('transaction_date', $request->transaction_date)->first();
        $timeInAM = null;
        $timeOutAM = null;
        $timeInPM = null;
        $timeOutPM = null;
        $timeInOT = null;
        $timeOutOT = null;

        if ($employeeDTR) {
            $timeInAM = $employeeDTR->loginam;
            $timeOutAM = $employeeDTR->logoutam;
            $timeInPM = $employeeDTR->loginpm;
            $timeOutPM = $employeeDTR->logoutpm;
            $timeInOT = $employeeDTR->loginot;
            $timeOutOT = $employeeDTR->logoutot;
        }
        // if (!$employeeTimeSchedule && BioStation::where('hwid', $request->hwid)->first()) {
            if ($employeeTimeSchedule->work_day_id != null) {
                $decodedData = json_decode($employeeTimeSchedule->workDays->data, true);
                $dayOfWeek = $transactionDate->format('D');
                // Access the data for "MON"
                $workingData = $decodedData['Days'][strtoupper($dayOfWeek)] ?? null;
                $workingAMIN = $workingData['timeInAM'] ? Carbon::createFromFormat('H:i', $workingData['timeInAM']) : Carbon::createFromFormat('H:i', "8:00");
                $workingAMOUT = $workingData['timeOutAM'] ? Carbon::createFromFormat('H:i', $workingData['timeOutAM']) : Carbon::createFromFormat('H:i', "12:00");
                $workingPMIN =  $workingData['timeInPM'] ? Carbon::createFromFormat('H:i', $workingData['timeInPM']) : Carbon::createFromFormat('H:i', "13:00");
                $workingPMOUT = $workingData['timeOutPM'] ? Carbon::createFromFormat('H:i', $workingData['timeOutPM']) : Carbon::createFromFormat('H:i', "17:00");
            } else {

                $workingAMIN = Carbon::createFromFormat('H:i', "8:00");
                $workingAMOUT = Carbon::createFromFormat('H:i', "12:00");
                $workingPMIN = Carbon::createFromFormat('H:i', "13:00");
                $workingPMOUT = Carbon::createFromFormat('H:i', "17:00");
            }

            $transactionTIme = Carbon::createFromFormat('H:i:s', $request->time); // 12:00 PM
            $interval = $workingAMOUT->diff($transactionTIme);
            $intervalAMIN = $workingAMIN->diff($transactionTIme);
            $intervalAMOUT = $workingAMOUT->diff($transactionTIme);
            $intervalPMIN = $workingPMIN->diff($transactionTIme);
            $intervalPMOUT = $workingPMOUT->diff($transactionTIme);


            $totalMinutes = ($interval->h * 60) + $interval->i;
            $totalMinutesAMIN = ($intervalAMIN->h * 60) + $intervalAMIN->i;
            $totalMinutesAMOUT = ($intervalAMOUT->h * 60) + $intervalAMOUT->i;
            $totalMinutesPMIN = ($intervalPMIN->h * 60) + $intervalPMIN->i;
            $totalMinutesPMOUT = ($intervalPMOUT->h * 60) + $intervalPMOUT->i;

            $data = [];
            //time in
            if (!$timeInAM && $totalMinutesAMOUT >= 30 && $transactionTIme->lt($workingAMOUT) && ($type == "Time In Am")) {
                // dd("Time IN AM " .$timeInAM."\n Transaction TIme:".$transactionTIme. "\n Work AM:".$workingAMOUT."\n Interval OUT:".$intervalAMOUT);
                $data = [
                    'employee_id' => $request->employee_id,
                    'transaction_date' => $request->transaction_date,

                    'loginam' => $request->time,
                    // Add other fields as needed
                ];
                $timesheet = Timesheet::updateOrCreate(
                    [
                        'employee_id' => $request->employee_id,
                        'transaction_date' => $request->transaction_date, // Conditions
                    ],
                    $data // Data to update or create
                );
            } else if ($totalMinutesPMIN > 28 && $transactionTIme->gt($workingAMIN) && $transactionTIme->lt($workingPMIN)&& ($type == "Time Out Am")) {
                // dd("Time OUT AM " .$timeInAM."\n Transaction TIme:".$transactionTIme. "\n Work PM IN:".$workingPMIN."\n Interval PM IN:".$intervalPMIN);
                $data = [
                    'employee_id' => $request->employee_id,
                    'transaction_date' => $request->transaction_date,

                    'logoutam' => $request->time,
                    // Add other fields as needed
                ];
                $timesheet = Timesheet::updateOrCreate(
                    [
                        'employee_id' => $request->employee_id,
                        'transaction_date' => $request->transaction_date, // Conditions
                    ],
                    $data // Data to update or create
                );
            } else if (
                $transactionTIme->lt($workingPMOUT) && $transactionTIme->gt($workingAMOUT) && !$timeInPM
                && $transactionTIme->lt($workingPMIN->addMinutes(119) && ( $type == "Time In Pm"))
            ) {
                // dd("Time IN PM");
                $data = [
                    'employee_id' => $request->employee_id,
                    'transaction_date' => $request->transaction_date,

                    'loginpm' => $request->time,
                    // Add other fields as needed
                ];
                $timesheet = Timesheet::updateOrCreate(
                    [
                        'employee_id' => $request->employee_id,
                        'transaction_date' => $request->transaction_date, // Conditions

                    ],
                    $data // Data to update or create
                );
            }  

            //time ot in
            else if ($type == "OT In") {
                // dd("Time OUT PM");
                $data = [
                    'employee_id' => $request->employee_id,
                    'transaction_date' => $request->transaction_date,

                    'loginot' => $request->time,
                    // Add other fields as needed
                ];
                $timesheet = Timesheet::updateOrCreate(
                    [
                        'employee_id' => $request->employee_id,
                        'transaction_date' => $request->transaction_date, // Conditions
                    ],
                    $data // Data to update or create
                );
            }

            //time ot in
            else if ($type == "OT Out") {
                // dd("Time OUT PM");
                $data = [
                    'employee_id' => $request->employee_id,
                    'transaction_date' => $request->transaction_date,

                    'logoutot' => $request->time,
                    // Add other fields as needed
                ];
                $timesheet = Timesheet::updateOrCreate(
                    [
                        'employee_id' => $request->employee_id,
                        'transaction_date' => $request->transaction_date, // Conditions
                    ],
                    $data // Data to update or create
                );
            }
            else {
            $data = [
                'employee_id' => $request->employee_id,
                'transaction_date' => $request->transaction_date,

                'logoutpm' => $request->time,
                // Add other fields as needed
            ];
            $timesheet = Timesheet::updateOrCreate(
                [
                    'employee_id' => $request->employee_id,
                    'transaction_date' => $request->transaction_date, // Conditions
                ],
                $data // Data to update or create
            );
        }
            TimeInOutLog::updateOrCreate(
                [
                    'employee_id' => $request->employee_id,
                    'date' => $request->transaction_date, // Conditions
                    'time_in_out' => $time,
                    'type' => $type,

                ],
                [
                    'employee_id' => $employee_id,
                    'time_in_out' => $time,
                    'date' => $transactionDate,
                    'type' => $type,
                    'device_name' => $device_name
                ]
            );
        // } 

        //     if($transactionTIme->lt($workingAMIN)||$transactionTIme->lt($workingAMOUT)&&($totalMinutesPMIN>=30)){
        //         //  dd("Time IN AM");
        //         $data = [
        //             'employee_id' => $request->employee_id,
        //             'transaction_date' => $request->transaction_date,

        //             'loginam' => $request->time,
        //             // Add other fields as needed
        //         ];
        //         $timesheet = Timesheet::updateOrCreate(
        //             [
        //                 'employee_id' => $request->employee_id,
        //                 'transaction_date' => $request->transaction_date, // Conditions
        //             ],
        //             $data // Data to update or create
        //         );


        //     }
        //     //time out am
        //     else if($transactionTIme->gt($workingAMIN)&&$transactionTIme->lt($workingPMIN)&&($totalMinutesPMIN>=30)){
        //         // dd("Time OUT AM");
        //         $data = [
        //             'employee_id' => $request->employee_id,
        //             'transaction_date' => $request->transaction_date,

        //             'logoutam' => $request->time,
        //             // Add other fields as needed
        //         ];
        //         $timesheet = Timesheet::updateOrCreate(
        //             [
        //                 'employee_id' => $request->employee_id,
        //                 'transaction_date' => $request->transaction_date, // Conditions
        //             ],
        //             $data // Data to update or create
        //         );
        //     }
        //     //time in pm
        //     else if($transactionTIme->lt($workingPMIN)||$transactionTIme->lt($workingPMOUT)&&($totalMinutesPMOUT>=90)  && ($type == "Time In" || $type == "Time In Pm")){
        //         // dd("Time IN PM");
        //         $data = [
        //             'employee_id' => $request->employee_id,
        //             'transaction_date' => $request->transaction_date,

        //             'loginpm' => $request->time,
        //             // Add other fields as needed
        //         ];
        //         $timesheet = Timesheet::updateOrCreate(
        //             [
        //                 'employee_id' => $request->employee_id,
        //                 'transaction_date' => $request->transaction_date, // Conditions

        //             ],
        //             $data // Data to update or create
        //         );
        //     }
        //     //time out pm
        //     else if($transactionTIme->gt($workingPMOUT)||$transactionTIme->lt($workingPMOUT)){
        //         // dd("Time OUT PM");
        //         $data = [
        //             'employee_id' => $request->employee_id,
        //             'transaction_date' => $request->transaction_date,

        //             'logoutpm' => $request->time,
        //             // Add other fields as needed
        //         ];
        //         $timesheet = Timesheet::updateOrCreate(
        //             [
        //                 'employee_id' => $request->employee_id,
        //                 'transaction_date' => $request->transaction_date, // Conditions
        //             ],
        //             $data // Data to update or create
        //         );
        //     }
        //      //time ot in
        //      else if($type == "OT In"){
        //         // dd("Time OUT PM");
        //         $data = [
        //             'employee_id' => $request->employee_id,
        //             'transaction_date' => $request->transaction_date,

        //             'loginot' => $request->time,
        //             // Add other fields as needed
        //         ];
        //         $timesheet = Timesheet::updateOrCreate(
        //             [
        //                 'employee_id' => $request->employee_id,
        //                 'transaction_date' => $request->transaction_date, // Conditions
        //             ],
        //             $data // Data to update or create
        //         );
        //     }

        //          //time ot in
        //          else if($type == "OT Out"){
        //             // dd("Time OUT PM");
        //             $data = [
        //                 'employee_id' => $request->employee_id,
        //                 'transaction_date' => $request->transaction_date,

        //                 'logoutot' => $request->time,
        //                 // Add other fields as needed
        //             ];
        //             $timesheet = Timesheet::updateOrCreate(
        //                 [
        //                     'employee_id' => $request->employee_id,
        //                     'transaction_date' => $request->transaction_date, // Conditions
        //                 ],
        //                 $data // Data to update or create
        //             );
        //         }
        //     TimeInOutLog::updateOrCreate(
        //         [
        //             'employee_id' => $request->employee_id,
        //             'date' => $request->transaction_date, // Conditions
        //             'time_in_out' => $time,
        //             'type' => $type,

        //         ],
        //     [
        //         'employee_id' => $employee_id,
        //         'time_in_out' => $time,
        //         'date' => $transactionDate,
        //         'type' => $type,
        //         'device_name' => $device_name
        //     ]);
        // }
        // else{

        //     $workingAMIN = Carbon::createFromFormat('H:i', "8:00"); 
        //     $workingAMOUT = Carbon::createFromFormat('H:i', "12:00"); 
        //     $workingPMIN = Carbon::createFromFormat('H:i', "13:00"); 
        //     $workingPMOUT = Carbon::createFromFormat('H:i', "17:00");
        // }



        // dd(strtoupper($dayOfWeek));

        // dd($data);
        // Update or create the timesheet entry


        return response()->json(
            [
                'status' => 1,
                'message' => 'Timesheet retrieved successfully',
                'timesheet' => $timesheet
            ],
            200
        ); // Return the updated or created instance as JSON
    }
}
