<?php

namespace SoftHouse\JasperReports\Console\Commands;

use Illuminate\Console\Command;
use SoftHouse\JasperReports\Exceptions\ErrorCompileReport;
use SoftHouse\JasperReports\Exceptions\FileCompileNotFound;
use SoftHouse\JasperReports\JasperReports;

class CompileReportsCommand extends Command
{
    protected $signature = 'reports:compile';

    protected $description = 'Compile files reports.';

    /**
     * @throws FileCompileNotFound
     * @throws ErrorCompileReport
     */
    public function handle()
    {
        $this->info("Compiling reports.");

        (new JasperReports())->queue(false)->compile();

        $this->info("Compiled reports.");
    }
}
