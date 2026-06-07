<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FlexibleHoursRequest extends Model
{
    use HasFactory;

    // Kita set nama table manual supaya tak error
    protected $table = 'flexible_hours_requests'; 

    protected $fillable = [
        'user_id',
        'date',
        'start_time',
        'end_time',
        'total_hours', // Simpan jam, contoh: 2.5
        'reason',
        'status',      // Pending, Approved, Rejected
        'admin_remark' // Komen admin
    ];

    // Relation: Request ini milik siapa?
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}