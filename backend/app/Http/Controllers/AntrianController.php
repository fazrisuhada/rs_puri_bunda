<?php

namespace App\Http\Controllers;

use App\Events\AntrianUpdated;
use App\Models\Antrian;
use App\Models\AntrianStatus;
use App\Models\Pelayanan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AntrianController extends Controller
{
    
    /**
     * Ambil list antrian
     */
    public function getAntrianPasien(Request $request)
    {
        $tanggal = $request->get('tanggal', now()->format('Y-m-d'));

        // Ambil semua antrian hari ini berdasarkan waktu kedatangan
        $allAntrians = Antrian::whereDate('tanggal_waktu', $tanggal)
            ->where('antrian_status_id', 1)
            ->orderBy('tanggal_waktu', 'asc')
            ->get();

        // Generate urutan pemanggilan
        $callOrder = Antrian::generateCallOrder($tanggal);

        return response()->json([
            'success' => true,
            'message' => 'Simulasi urutan pemanggilan',
            'data' => [
                'urutan_pasien_masuk' => $allAntrians->map(function ($antrian, $index) {
                    return [
                        'urutan' => $index + 1,
                        'nomor_antrian' => $antrian->nomor_antrian,
                        'jenis_antrian' => $antrian->jenis_antrian,
                        'waktu_kedatangan' => $antrian->tanggal_waktu
                    ];
                }),
                'urutan_pemanggilan' => $callOrder->map(function ($antrian, $index) {
                    return [
                        'urutan' => $index + 1,
                        'antrian_id' => $antrian->id,
                        'nomor_antrian' => $antrian->nomor_antrian,
                        'jenis_antrian' => $antrian->jenis_antrian,
                        'waktu_kedatangan' => $antrian->tanggal_waktu,
                        'antrian_status_id' => $antrian->antrian_status_id
                    ];
                })
            ]
        ]);
    }
    
    /**
     * Buat antrian baru
     */
    public function store(Request $request)
    {
        $request->validate([
            'jenis_antrian' => 'required|in:reservasi,walk-in'
        ]);

        $nomorAntrian = Antrian::generateNomorAntrian($request->jenis_antrian);

        $antrian = Antrian::create([
            'nomor_antrian' => $nomorAntrian,
            'jenis_antrian' => $request->jenis_antrian,
            'antrian_status_id' => 1, // default: waiting
            'tanggal_waktu' => now()
        ]);

        $antrian->load('status');

        return response()->json([
            'success' => true,
            'message' => 'Antrian berhasil dibuat',
            'data' => $antrian
        ], 201);
    }

    /**
     * Panggil antrian berikutnya berdasarkan skema 2 Reservasi : 1 Walk-in
     */
    public function panggilPasien(Request $request)
    {
        $staff_id = auth()->user()->id;

        if (!$staff_id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. User not authenticated.'
            ], 401);
        }

        $nomorAntrian = $request->input('nomor_antrian');

        $allAntrian = Antrian::select('id', 'nomor_antrian', 'antrian_status_id')
            ->orderBy('id')
            ->get();

        if ($nomorAntrian) {
            $nextAntrian = Antrian::where('nomor_antrian', $nomorAntrian)->first();
        } else {
            $nextAntrian = Antrian::getNextInQueue();
        }

        if (!$nextAntrian) {
            return response()->json([
                'success' => false,
                'message' => 'Antrian tidak ditemukan',
                'debug' => [
                    'searching_for' => $nomorAntrian,
                    'type' => gettype($nomorAntrian),
                    'all_data' => $allAntrian->toArray()
                ]
            ], 404);
        }

        if (!$nextAntrian) {
            return response()->json([
                'success' => false,
                'message' => 'Antrian tidak ditemukan',
            ], 404);
        }

        // Update status
        $nextAntrian->update([
            'antrian_status_id' => 2
        ]);

        // Cek apakah sudah ada pelayanan
        $pelayanan = Pelayanan::where('antrian_id', $nextAntrian->id)->first();

        if (!$pelayanan) {
            $pelayanan = Pelayanan::create([
                'antrian_id' => $nextAntrian->id,
                'staff_id' => $staff_id,
                'waktu_mulai' => now(),
                'catatan' => 'Pelayanan oleh staff ' . $staff_id
            ]);
        } else {
            $pelayanan->update([
                'staff_id' => $staff_id,
                'catatan' => 'Panggil ulang oleh staff ' . $staff_id
            ]);
        }

        // Load relasi yang dibutuhkan
        $nextAntrian->load(['status', 'pelayanan.staff']);
        // dd($nextAntrian->pelayanan);

        // Broadcast event
        broadcast(new AntrianUpdated($nextAntrian));

        return response()->json([
            'success' => true,
            'message' => 'Antrian berhasil dipanggil',
            'data' => $nextAntrian
        ]);
    }

    /**
     * Panggil pasien selanjutnya
     */
    public function panggilPasienSelanjutnya(Request $request)
    {
        $user = auth()->user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. User not authenticated.'
            ], 401);
        }
        $staff_id = $user->id;

        // Ambil urutan panggil berdasarkan skema 2 reservasi : 1 walk-in
        $callOrder = Antrian::generateCallOrder();

        // Cari antrian pertama yang statusnya masih menunggu (1)
        $nextAntrian = $callOrder->firstWhere('antrian_status_id', 1);

        if (!$nextAntrian) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak ada antrian yang menunggu'
            ], 404);
        }

        // Update status jadi dipanggil (2)
        $nextAntrian->update([
            'antrian_status_id' => 2
        ]);

        // Buat atau update record pelayanan
        Pelayanan::firstOrCreate([
            'antrian_id' => $nextAntrian->id,
        ], [
            'staff_id' => $staff_id,
            'waktu_mulai' => now(),
            'catatan' => 'Pemanggilan oleh staff ' . $staff_id
        ]);

        // Load relasi status untuk response
        $nextAntrian->load('status');

        // Broadcast event
        broadcast(new AntrianUpdated($nextAntrian));

        return response()->json([
            'success' => true,
            'message' => 'Antrian berhasil dipanggil',
            'data' => $nextAntrian
        ]);
    }

    /**
     * Selesaikan antrian (status called -> done)
     */
    public function selesaiDipanggil($antrian_id)
    {
        $antrian = Antrian::with('pelayanan')->find($antrian_id);

        if (!$antrian) {
            return response()->json([
                'success' => false,
                'message' => 'Antrian tidak ditemukan'
            ], 404);
        }

        // Update status antrian menjadi done
        $antrian->update([
            'antrian_status_id' => 3 // done
        ]);

        // Update waktu selesai pelayanan
        if ($antrian->pelayanan) {
            $antrian->pelayanan->update([
                'waktu_selesai' => now()
            ]);
        }

        // Load ulang relasi
        $antrian->load(['status', 'pelayanan.staff']);

        // Broadcast event agar Display statusnya berubah
        broadcast(new AntrianUpdated($antrian));

        return response()->json([
            'success' => true,
            'message' => 'Antrian berhasil diselesaikan',
            'data' => $antrian
        ]);
    }
}
