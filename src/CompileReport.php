<?php

namespace SoftHouse\JasperReports;

use SoftHouse\JasperReports\Exceptions\ErrorCompileReport;

class CompileReport
{
    /**
     * @throws ErrorCompileReport
     */
    public static function run(bool $systemOS, string $input, string $output)
    {
        try {

            $command = sprintf("%sreport compile %s -o %s", $systemOS ? '' : './', $input, $output);

            $outputExec = [];
            $returnVar = 0;

            chdir(__DIR__ . '/jasper/bin');
            exec($command, $outputExec, $returnVar);
            if ($returnVar !== 0) {
                throw new ErrorCompileReport(implode(", ", $outputExec));
            }
        } catch (\Exception $exception) {
            throw new ErrorCompileReport($exception);
        }
    }
}
