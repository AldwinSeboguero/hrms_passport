<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Timesheet extends Model
{
    protected $table='attendance_records';
    protected $fillable=['employee_id','loginam','logoutam','loginpm','logoutpm', 'loginot','logoutot','transaction_date'];


}
