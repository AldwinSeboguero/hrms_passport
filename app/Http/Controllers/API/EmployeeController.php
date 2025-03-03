<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Employee;
use App\Models\Timesheet;
use Carbon\Carbon;
class EmployeeController extends Controller
{
    //
    public function index(Request $request){
        $employees = Employee::paginate(5);

        return response()->json([
            'status' => 1,
            'message' => 'Employees retrieved successfully',
            'data' => $employees
        ]);
    }

    public function getTimeRecord(Request $request){
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

    public function updateOrCreateTimesheet(Request $request) {
        // Validate the incoming request
        // $request->validate([
        //     'employeeId' => 'required|integer',
        //     'transactionDate' => 'required|date', 
        // ]);
    
        // Prepare the data to update or create
        $transactionDate = Carbon::parse($request->transaction_date);
        $timesheet =null;

    // Format the day of the week as a three-letter abbreviation

        $employeeTimeSchedule = Employee::find($request->employee_id);
        // dd($employeeTimeSchedule);
        if($employeeTimeSchedule){
            if($employeeTimeSchedule->work_day_id != null){
                $decodedData = json_decode($employeeTimeSchedule->workDays->data, true);
                $dayOfWeek = $transactionDate->format('D');
                // Access the data for "MON"
                $workingData = $decodedData['Days'][strtoupper($dayOfWeek)] ?? null; 
                $workingAMIN = Carbon::createFromFormat('H:i', $workingData['timeInAM']); 
                $workingAMOUT = Carbon::createFromFormat('H:i', $workingData['timeOutAM']); 
                $workingPMIN = Carbon::createFromFormat('H:i', $workingData['timeInPM']); 
                $workingPMOUT = Carbon::createFromFormat('H:i', $workingData['timeOutPM']);
            }
            else{
        
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
    
            $data = [ 
            ];
            //time in
            if($transactionTIme->lt($workingAMIN)||$transactionTIme->lt($workingAMOUT)&&($totalMinutesAMOUT>=90)){
                //  dd("Time IN AM");
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
            }
            //time out am
            else if($transactionTIme->gt($workingAMIN)&&$transactionTIme->lt($workingPMIN)&&($totalMinutesAMOUT<=30)){
                // dd("Time OUT AM");
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
            }
            //time in pm
            else if($transactionTIme->lt($workingPMIN)||$transactionTIme->lt($workingPMOUT)&&($totalMinutesPMOUT>=90)){
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
            //time out pm
            else if($transactionTIme->gt($workingPMOUT)||$transactionTIme->lt($workingPMOUT)){
                // dd("Time OUT PM");
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
        }
        // else{
        
        //     $workingAMIN = Carbon::createFromFormat('H:i', "8:00"); 
        //     $workingAMOUT = Carbon::createFromFormat('H:i', "12:00"); 
        //     $workingPMIN = Carbon::createFromFormat('H:i', "13:00"); 
        //     $workingPMOUT = Carbon::createFromFormat('H:i', "17:00");
        // }
        

       
        // dd(strtoupper($dayOfWeek));
      
    // dd($data);
        // Update or create the timesheet entry
     
    
        return response()->json([
            'status' => 1,
            'message' => 'Timesheet retrieved successfully',
            'timesheet' => $timesheet]
            , 200); // Return the updated or created instance as JSON
    }
}
