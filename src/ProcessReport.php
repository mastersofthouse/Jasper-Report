<?php

namespace SoftHouse\JasperReports;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use SoftHouse\JasperReports\Exceptions\ErrorCompileReport;
use SoftHouse\JasperReports\Exceptions\ErrorProcessReport;

class ProcessReport
{
    /**
     * @throws ErrorProcessReport
     */
    public static function run(bool    $systemOS, string $name, string $input, string $locale, string $format,
                               ?string $resources, $dataAdapter, ?string $params, string $protect, string $disk): string
    {
        try {

            $storage = storage_path("reports/" . Str::random(32));

            if (!File::exists($storage)) {
                File::makeDirectory($storage);
            }

            $command = sprintf("%sreport --locale %s process %s %s %s -f %s %s -o %s %s  2>&1",
                $systemOS ? '' : './', $locale, $input,
                is_null($resources) ? '' : "-r ${resources}",
                $dataAdapter, $format, is_null($params) ? "" : "-P ${params}", $storage, $protect);


            $outputExec = [];
            $returnVar = 0;
            chdir(__DIR__ . '/jasper/bin');
            exec($command, $outputExec, $returnVar);

            if ($returnVar !== 0) {

                throw new ErrorCompileReport(implode(", ", $outputExec));
            }

            $pathDisk = Str::random(32) . DIRECTORY_SEPARATOR . $name . ".${format}";
            $pathStorage = $storage . DIRECTORY_SEPARATOR . $name . ".${format}";

            Storage::disk($disk)->put($pathDisk, file_get_contents($pathStorage));
            File::deleteDirectory($storage);

            return Storage::disk($disk)->url($pathDisk);
        } catch (\Exception $exception) {
            throw new ErrorProcessReport($exception);
        }
    }
}
