<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AntrianUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $antrian;

    public function __construct($antrian)
    {
        $this->antrian = $antrian->load(['pelayanan.staff']);
    }

    public function broadcastOn()
    {
        return new Channel('antrian-channel');
    }

    public function broadcastAs()
    {
        return 'antrian.updated';
    }

    public function broadcastWith()
    {
        return [
            'antrian' => [
                'id' => $this->antrian->id,
                'antrian_id' => $this->antrian->id,
                'nomor_antrian' => $this->antrian->nomor_antrian,
                'jenis_antrian' => $this->antrian->jenis_antrian,
                'antrian_status_id' => $this->antrian->antrian_status_id,
                'waktu_dipanggil' => optional($this->antrian->pelayanan)->waktu_mulai,
                'staff_name' => optional(optional($this->antrian->pelayanan)->staff)->name ?? null,
                'loket_name' => 'Loket Pendaftaran',
            ]
        ];
    }
}