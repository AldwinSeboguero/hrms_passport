<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TimeInOutLog extends Model
{
    protected $fillable=['employee_id','time_in_out','date','type','device_name'];
}
