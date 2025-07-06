<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Antrian extends Model
{
    use HasFactory;

    protected $table = 'antrian';
    protected $fillable = [
        'nomor_antrian',
        'jenis_antrian',
        'antrian_status_id',
        'tanggal_waktu'
    ];

    protected $casts = [
        'tanggal_waktu' => 'datetime',
    ];

    // Relasi dengan antrian_statuses
    public function status()
    {
        return $this->belongsTo(StatusAntrian::class, 'antrian_status_id');
    }
    
    // Scope untuk filter berdasarkan status
    public function scopeWaiting($query)
    {
        return $query->where('antrian_status_id', 1);
    }

    public function scopeCalled($query)
    {
        return $query->where('antrian_status_id', 2);
    }

    public function scopeDone($query)
    {
        return $query->where('antrian_status_id', 3);
    }

    // Relasi dengan pelayanan
    public function pelayanan()
    {
        return $this->hasOne(Pelayanan::class);
    }

    // Generate nomor antrian otomatis
    public static function generateNomorAntrian($jenis)
    {
        $prefix = $jenis === 'reservasi' ? 'R' : 'W';
        $today = now()->format('Y-m-d');

        // Cari nomor terakhir untuk hari ini
        $lastNumber = self::where('jenis_antrian', $jenis)
            ->whereDate('tanggal_waktu', $today)
            ->where('nomor_antrian', 'like', $prefix . '%')
            ->orderBy('nomor_antrian', 'desc')
            ->first();

        if ($lastNumber) {
            // Ambil angka dari nomor terakhir
            $lastNum = intval(substr($lastNumber->nomor_antrian, 1));
            $newNum = $lastNum + 1;
        } else {
            $newNum = 1;
        }

        return $prefix . str_pad($newNum, 3, '0', STR_PAD_LEFT);
    }

    // Scope untuk filter berdasarkan jenis
    public function scopeReservasi($query)
    {
        return $query->where('jenis_antrian', 'reservasi');
    }

    public function scopeWalkIn($query)
    {
        return $query->where('jenis_antrian', 'walk-in');
    }

    // Generate urutan pemanggilan dengan skema 2 Reservasi : 1 Walk-in
    public static function generateCallOrder($tanggal = null)
    {
        $tanggal = $tanggal ?? now()->format('Y-m-d');

        // Ambil antrian yang waiting untuk hari ini berdasarkan waktu kedatangan
        $reservasis = self::where('jenis_antrian', 'reservasi')
            ->whereIn('antrian_status_id', [1, 2]) // waiting dan called
            ->whereDate('tanggal_waktu', $tanggal)
            ->orderBy('tanggal_waktu', 'asc')
            ->get();

        $walkins = self::where('jenis_antrian', 'walk-in')
            ->whereIn('antrian_status_id', [1, 2]) // waiting dan called
            ->whereDate('tanggal_waktu', $tanggal)
            ->orderBy('tanggal_waktu', 'asc')
            ->get();

        $callOrder = [];
        $resIndex = 0;
        $walkIndex = 0;
        $reservasiCount = 0; // Counter untuk melacak berapa reservasi berturut-turut yang sudah dipanggil

        // Algoritma sederhana: terus loop sampai semua antrian selesai
        while ($resIndex < $reservasis->count() || $walkIndex < $walkins->count()) {
            // Jika sudah 2 reservasi berturut-turut, wajib panggil walk-in (jika ada)
            if ($reservasiCount >= 2 && $walkIndex < $walkins->count()) {
                $callOrder[] = $walkins[$walkIndex];
                $walkIndex++;
                $reservasiCount = 0; // Reset counter setelah walk-in
            }
            // Jika belum 2 reservasi berturut-turut, prioritaskan reservasi (jika ada)
            elseif ($reservasiCount < 2 && $resIndex < $reservasis->count()) {
                $callOrder[] = $reservasis[$resIndex];
                $resIndex++;
                $reservasiCount++;
            }
            // Jika tidak ada reservasi lagi, panggil walk-in
            elseif ($walkIndex < $walkins->count()) {
                $callOrder[] = $walkins[$walkIndex];
                $walkIndex++;
                $reservasiCount = 0; // Reset counter setelah walk-in
            }
            // Jika tidak ada walk-in lagi, panggil reservasi
            elseif ($resIndex < $reservasis->count()) {
                $callOrder[] = $reservasis[$resIndex];
                $resIndex++;
                $reservasiCount++;
            }
            else {
                break; // Semua antrian sudah selesai
            }
        }

        return collect($callOrder);
    }

    // Ambil antrian berikutnya sesuai skema
    public static function getNextInQueue($tanggal = null)
    {
        $callOrder = self::generateCallOrder($tanggal);
        return $callOrder->first();
    }
}