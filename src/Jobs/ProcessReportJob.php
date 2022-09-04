<?php

namespace SoftHouse\JasperReports\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use SoftHouse\JasperReports\Events\ReportGenerated;
use SoftHouse\JasperReports\Exceptions\ErrorProcessReport;
use SoftHouse\JasperReports\ProcessReport;

class ProcessReportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public bool $systemOS;
    public string $name;
    public string $input;
    public string $output;
    public string $locale;
    public string $format;
    public string $resources;
    public $dataAdapter;
    public ?string $params = null;
    public string $protect;
    public string $disk;
    public array $extra;

    public function __construct(bool $systemOS, string $name, string $input, string $locale, string $format,
                                ?string $resources, $dataAdapter, ?string $params, string $protect, string $disk, array $extra)
    {
        $this->delay = \Carbon\Carbon::now()->addSeconds(2);
        $this->systemOS = $systemOS;
        $this->name = $name;
        $this->input = $input;
        $this->locale = $locale;
        $this->format = $format;
        $this->resources = $resources;
        $this->dataAdapter = $dataAdapter;
        $this->params = $params;
        $this->protect = $protect;
        $this->disk = $disk;
        $this->extra = $extra;
    }

    /**
     * Execute the job.
     *
     * @return void
     * @throws ErrorProcessReport
     */
    public function handle()
    {
        try {
            $storage = ProcessReport::run($this->systemOS, $this->name, $this->input, $this->locale, $this->format, $this->resources,
                $this->dataAdapter, $this->params, $this->protect, $this->disk);

            event(new ReportGenerated($storage, $this->extra));
        } catch (\Exception $exception) {
            throw new ErrorProcessReport($exception);
        }
    }
}
