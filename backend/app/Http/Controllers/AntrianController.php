<?php

namespace App\Http\Controllers;

use App\Models\Antrian;
use App\Models\AntrianStatus;
use App\Models\Pelayanan;
use Illuminate\Http\Request;

class AntrianController extends Controller
{
    /**
     * Ambil semua antrian
     */
    public function index(Request $request)
    {
        $query = Antrian::with('status');

        // Filter berdasarkan jenis antrian
        if ($request->has('jenis')) {
            $query->where('jenis_antrian', $request->jenis);
        }

        // Filter berdasarkan status
        if ($request->has('status_id')) {
            $query->where('antrian_status_id', $request->status_id);
        }

        // Filter berdasarkan tanggal
        if ($request->has('tanggal')) {
            $query->whereDate('tanggal_waktu', $request->tanggal);
        }

        // Perbaikan urutan: berdasarkan waktu kedatangan (asc) bukan desc
        $antrians = $query->orderBy('tanggal_waktu', 'asc')->get();

        return response()->json([
            'success' => true,
            'data' => $antrians
        ]);
    }

    /**
     * Buat antrian baru iya
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
     * Ambil detail antrian
     */
    public function show($id)
    {
        $antrian = Antrian::with('status')->find($id);

        if (!$antrian) {
            return response()->json([
                'success' => false,
                'message' => 'Antrian tidak ditemukan'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $antrian
        ]);
    }

    /**
     * Update status antrian
     */
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status_id' => 'required|exists:status_antrian,id'
        ]);

        $antrian = Antrian::find($id);

        if (!$antrian) {
            return response()->json([
                'success' => false,
                'message' => 'Antrian tidak ditemukan'
            ], 404);
        }

        $antrian->update([
            'antrian_status_id' => $request->status_id
        ]);

        $antrian->load('status');

        return response()->json([
            'success' => true,
            'message' => 'Status antrian berhasil diupdate',
            'data' => $antrian
        ]);
    }

    /**
     * Panggil antrian berikutnya berdasarkan skema 2 Reservasi : 1 Walk-in iya
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

        if ($nomorAntrian) {
            $nextAntrian = Antrian::where('nomor_antrian', $nomorAntrian)->first();
        } else {
            $nextAntrian = Antrian::getNextInQueue();
        }

        if (!$nextAntrian) {
            return response()->json([
                'success' => false,
                'message' => 'Antrian tidak ditemukan'
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

        $nextAntrian->load(['status', 'pelayanan.staff']);

        return response()->json([
            'success' => true,
            'message' => 'Antrian berhasil dipanggil',
            'data' => $nextAntrian
        ]);
    }
    
    /**
     * Panggil pasien selanjutnya iya
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

        return response()->json([
            'success' => true,
            'message' => 'Antrian berhasil dipanggil',
            'data' => $nextAntrian
        ]);
    }

    /**
     * Selesaikan antrian (status called -> done) iya
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

        $antrian->load(['status', 'pelayanan.staff']);

        return response()->json([
            'success' => true,
            'message' => 'Antrian berhasil diselesaikan',
            'data' => $antrian
        ]);
    }

    /**
     * Hapus antrian
     */
    public function destroy($id)
    {
        $antrian = Antrian::find($id);

        if (!$antrian) {
            return response()->json([
                'success' => false,
                'message' => 'Antrian tidak ditemukan'
            ], 404);
        }

        $antrian->delete();

        return response()->json([
            'success' => true,
            'message' => 'Antrian berhasil dihapus'
        ]);
    }

    /**
     * Ambil statistik antrian hari ini
     */
    public function todayStats()
    {
        $today = now()->format('Y-m-d');

        $stats = [
            'total' => Antrian::whereDate('tanggal_waktu', $today)->count(),
            'reservasi' => Antrian::whereDate('tanggal_waktu', $today)->where('jenis_antrian', 'reservasi')->count(),
            'walk_in' => Antrian::whereDate('tanggal_waktu', $today)->where('jenis_antrian', 'walk-in')->count(),
            'waiting' => Antrian::whereDate('tanggal_waktu', $today)->where('antrian_status_id', 1)->count(),
            'called' => Antrian::whereDate('tanggal_waktu', $today)->where('antrian_status_id', 2)->count(),
            'done' => Antrian::whereDate('tanggal_waktu', $today)->where('antrian_status_id', 3)->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    /**
     * Lihat urutan pemanggilan antrian berdasarkan skema 2:1
     */
    public function callOrder(Request $request)
    {
        $tanggal = $request->get('tanggal', now()->format('Y-m-d'));
        $callOrder = Antrian::generateCallOrder($tanggal);

        return response()->json([
            'success' => true,
            'message' => 'Urutan pemanggilan antrian (2 Reservasi : 1 Walk-in)',
            'data' => $callOrder->map(function ($antrian, $index) {
                return [
                    'urutan' => $index + 1,
                    'nomor_antrian' => $antrian->nomor_antrian,
                    'jenis_antrian' => $antrian->jenis_antrian,
                    'tanggal_waktu' => $antrian->tanggal_waktu,
                    'status' => $antrian->status->name ?? 'Unknown'
                ];
            })
        ]);
    }

    /**
     * Lihat antrian berikutnya yang akan dipanggil
     */
    public function nextQueue(Request $request)
    {
        $tanggal = $request->get('tanggal', now()->format('Y-m-d'));
        $nextAntrian = Antrian::getNextInQueue($tanggal);

        if (!$nextAntrian) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak ada antrian berikutnya'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Antrian berikutnya yang akan dipanggil',
            'data' => [
                'nomor_antrian' => $nextAntrian->nomor_antrian,
                'jenis_antrian' => $nextAntrian->jenis_antrian,
                'tanggal_waktu' => $nextAntrian->tanggal_waktu
            ]
        ]);
    }

    /**
     * Lihat riwayat pelayanan
     */
    public function pelayananHistory(Request $request)
    {
        $query = Pelayanan::with(['antrian', 'staff']);

        // Filter berdasarkan staff
        if ($request->has('staff_id')) {
            $query->where('staff_id', $request->staff_id);
        }

        // Filter berdasarkan tanggal
        if ($request->has('tanggal')) {
            $query->whereDate('waktu_mulai', $request->tanggal);
        }

        $pelayanan = $query->orderBy('waktu_mulai', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => $pelayanan->map(function ($p) {
                return [
                    'id' => $p->id,
                    'nomor_antrian' => $p->antrian->nomor_antrian,
                    'jenis_antrian' => $p->antrian->jenis_antrian,
                    'staff' => $p->staff->name,
                    'waktu_mulai' => $p->waktu_mulai,
                    'waktu_selesai' => $p->waktu_selesai,
                    'durasi_menit' => $p->durasi,
                    'catatan' => $p->catatan
                ];
            })
        ]);
    }

    /**
     * Simulasi untuk testing urutan pemanggilan iya
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
}
