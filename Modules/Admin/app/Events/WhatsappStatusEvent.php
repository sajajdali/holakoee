<?php

namespace Modules\Admin\app\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Queue\SerializesModels;

class WhatsappStatusEvent implements ShouldBroadcastNow
{
    use SerializesModels;

    public $status;
    public $session;

    /**
     * @param $status
     * @param $session
     */
    public function __construct($session,$status )
    {
        $this->session = $session;
        $this->status = $status;
    }
    /**
     * Create a new event instance.
     */
    public function broadcastOn()
    {
        return new Channel("whatsapp.{$this->session}"); // public
    }

    public function broadcastAs()
    {
        return 'status.change';
    }

    public function broadcastWith(): array
    {
        return ['session' => $this->session, 'status' => $this->status];
    }
}
