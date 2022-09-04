<?php

namespace SoftHouse\JasperReports\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use SoftHouse\JasperReports\CompileReport;
use SoftHouse\JasperReports\Exceptions\ErrorCompileReport;

class CompileReportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public bool $systemOS;
    public string $input;
    public string $output;

    public function __construct(bool $systemOS, string $input, string $output)
    {
        $this->delay = \Carbon\Carbon::now()->addSeconds(2);
        $this->systemOS = $systemOS;
        $this->input = $input;
        $this->output = $output;
    }

    /**
     * Execute the job.
     *
     * @return void
     * @throws ErrorCompileReport
     */
    public function handle()
    {
        try {
            CompileReport::run($this->systemOS, $this->input, $this->output);
        } catch (\Exception $exception) {
            throw new ErrorCompileReport($exception);
        }
    }
}
