<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User;

class ParkingTransfert extends Model
{
    protected $fillable = ['parking_id', 'old_user_id', 'new_user_id', 'performed_by'];

    public function parking()
    {
        return $this->belongsTo(Parking::class);
    }
    public function oldUser()
    {
        return $this->belongsTo(User::class, 'old_user_id');
    }
    public function newUser()
    {
        return $this->belongsTo(User::class, 'new_user_id');
    }
    public function actor()
    {
        return $this->belongsTo(User::class, 'performed_by');
    }
}
