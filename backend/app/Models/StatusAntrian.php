<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StatusAntrian extends Model
{
    use HasFactory;

    protected $table = 'status_antrian';
    protected $fillable = [
        'name'
    ];

    public $timestamps = false;

    // Relasi dengan antrian
    public function antrians()
    {
        return $this->hasMany(Antrian::class);
    }
}