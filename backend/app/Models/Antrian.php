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

    // Relasi dengan status antrian
    public function status()
    {
        return $this->belongsTo(StatusAntrian::class, 'antrian_status_id');
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

    // Generate urutan pemanggilan dengan skema 2 Reservasi : 1 Walk-in
    public static function generateCallOrder($tanggal = null)
    {
        $tanggal = $tanggal ?? now()->format('Y-m-d');

        // Ambil semua antrian hari ini yang statusnya menunggu/dipanggil
        $all = self::whereIn('antrian_status_id', [1, 2])
            ->whereDate('tanggal_waktu', $tanggal)
            ->orderBy('tanggal_waktu', 'asc')
            ->get();

        $callOrder = collect();
        // $reservasiCount = 0;

        // Cek jenis antrian pertama hari ini
        $first = $all->first();
        $startWithWalkin = $first && $first->jenis_antrian === 'walk-in';

        // Jika mulai dari walk-in, panggil dahulu
        $walkinQueue = $all->filter(fn($a) => $a->jenis_antrian === 'walk-in')->values();
        $reservasiQueue = $all->filter(fn($a) => $a->jenis_antrian === 'reservasi')->values();

        $walkIndex = 0;
        $resIndex = 0;

        // Jika antrian pertama adalah walk-in, panggil dahulu
        if ($startWithWalkin && $walkIndex < $walkinQueue->count()) {
            $callOrder->push($walkinQueue[$walkIndex]);
            $walkIndex++;
        }

        // Mulai skema 2R : 1W
        while ($resIndex < $reservasiQueue->count() || $walkIndex < $walkinQueue->count()) {
            // Panggil 2 reservasi (jika ada)
            for ($i = 0; $i < 2 && $resIndex < $reservasiQueue->count(); $i++) {
                $callOrder->push($reservasiQueue[$resIndex]);
                $resIndex++;
            }

            // Panggil 1 walk-in (jika ada)
            if ($walkIndex < $walkinQueue->count()) {
                $callOrder->push($walkinQueue[$walkIndex]);
                $walkIndex++;
            }
        }

        return $callOrder;
    }

    // Ambil antrian berikutnya sesuai skema
    public static function getNextInQueue($tanggal = null)
    {
        $callOrder = self::generateCallOrder($tanggal);
        return $callOrder->first();
    }
}