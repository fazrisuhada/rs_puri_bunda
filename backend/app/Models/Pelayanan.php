<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pelayanan extends Model
{
    use HasFactory;

    protected $table = 'pelayanan';
    protected $fillable = [
        'antrian_id',
        'staff_id',
        'waktu_mulai',
        'waktu_selesai',
        'catatan'
    ];

    protected $casts = [
        'waktu_mulai' => 'datetime',
        'waktu_selesai' => 'datetime',
    ];

    // Relasi dengan antrian
    public function antrian()
    {
        return $this->belongsTo(Antrian::class);
    }

    // Relasi dengan user (staff)
    public function staff()
    {
        return $this->belongsTo(User::class, 'staff_id');
    }

    // Hitung durasi pelayanan
    public function getDurasiAttribute()
    {
        if ($this->waktu_selesai) {
            return $this->waktu_mulai->diffInMinutes($this->waktu_selesai);
        }
        return null;
    }
}