<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OvertimeRequest extends Model
{
    use HasFactory;
    protected $guarded = []; // Benarkan semua column diisi

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}