<?php

namespace App\Http\Controllers;

use App\Models\Antrian;
use App\Models\Pelayanan;
use App\Models\User; // Anggap User adalah model staff
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Tampilkan dashboard monitoring data antrian dan staff.
     */
    public function index(Request $request)
    {
        $jumlahAntrianAktif = Antrian::where('antrian_status_id', 1)->count();

        $jumlahStaffAktif = User::where('status', true)->count();

        $topStaff = Pelayanan::select('staff_id', DB::raw('count(*) as total_pelayanan'))
            ->groupBy('staff_id')
            ->orderByDesc('total_pelayanan')
            ->take(3)
            ->with('staff')
            ->get();

        $statistikWaktu = DB::table('pelayanan')
            ->join('users', 'pelayanan.staff_id', '=', 'users.id')
            ->select(
                'pelayanan.staff_id',
                'users.name as nama_staff',
                DB::raw('AVG(EXTRACT(EPOCH FROM (waktu_selesai - waktu_mulai)) / 60) as rata_rata_menit')
            )
            ->whereNotNull('waktu_mulai')
            ->whereNotNull('waktu_selesai')
            ->groupBy('pelayanan.staff_id', 'users.name')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Data dashboard berhasil diambil',
            'data' => [
                'jumlah_antrian_aktif' => $jumlahAntrianAktif,
                'jumlah_staff_aktif' => $jumlahStaffAktif,
                'top_staff' => $topStaff->map(function ($item) {
                    return [
                        'staff_id' => $item->staff_id,
                        'nama_staff' => $item->staff ? $item->staff->name : null,
                        'total_pelayanan' => $item->total_pelayanan,
                    ];
                }),
                'statistik_waktu_pelayanan' => $statistikWaktu->map(function ($item) {
                    return [
                        'staff_id' => $item->staff_id,
                        'nama_staff' => $item->nama_staff,
                        'rata_rata_waktu_menit' => round($item->rata_rata_menit, 2),
                    ];
                }),
            ],
        ]);
    }
}
