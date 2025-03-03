<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
class Employee extends Model
{
    // use HasFactory;
    // public function position()
    // {
    //     return $this->belongsTo('App\Models\Position','position_id');
    // }
    // public function division()
    // {
    //     return $this->belongsTo('App\Models\Division','division_id');
    // }
    // public function employeeType()
    // {
    //     return $this->belongsTo('App\Models\EmployeeType','employee_type_id');
    // }
    public function workDays()
    {
        return $this->belongsTo('App\Models\WorkDay','work_day_id');
    }
    protected $casts = [
        'date_of_birth' => 'datetime',
        'start_date' => 'datetime'

    ];
    public function getAgeAttribute()
    {
        if ($this->date_of_birth) {
            // Get the current date
            $currentDate = Carbon::now();

            // Ensure date_of_birth is a valid date
            $dob = Carbon::parse($this->date_of_birth);

            // Check if date_of_birth is in the future
            if ($dob > $currentDate) {
                return 0; // or handle as needed
            }

            // Calculate age and ensure it's a positive integer
            return (int) $currentDate->diffInYears($dob) * -1;
        }

        return 0; 
    }

}
