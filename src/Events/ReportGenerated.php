<?php

namespace SoftHouse\JasperReports\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ReportGenerated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public string $output;
    public array $extra;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(string $output, array $extra = [])
    {
        $this->output = $output;
        $this->extra = $extra;
    }
}
