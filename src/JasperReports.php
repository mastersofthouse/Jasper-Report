<?php

namespace SoftHouse\JasperReports;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use SoftHouse\JasperReports\Events\ReportGenerated;
use SoftHouse\JasperReports\Exceptions\ErrorCompileReport;
use SoftHouse\JasperReports\Exceptions\ErrorDataAdapterReport;
use SoftHouse\JasperReports\Exceptions\ErrorFormatReport;
use SoftHouse\JasperReports\Exceptions\ErrorResourcesReport;
use SoftHouse\JasperReports\Exceptions\FileCompileNotFound;
use SoftHouse\JasperReports\Exceptions\FileReportNotFound;
use SoftHouse\JasperReports\Exceptions\TypeReportNotFound;
use SoftHouse\JasperReports\Jobs\CompileReportJob;
use SoftHouse\JasperReports\Jobs\ProcessReportJob;

class JasperReports
{
    protected array $config;
    private ?bool $queue = null;

    public function __construct()
    {
        $this->config = config('jasper-reports');
    }

    public function queue($status): JasperReports
    {
        $this->queue = $status;
        return $this;
    }

    /**
     * @throws FileCompileNotFound
     * @throws ErrorCompileReport
     */
    public function compile()
    {
        $path = $this->config["path"];
        $templates = $this->config["templates"];
        $reports = $this->config["reports"];

        $queue = is_null($this->queue) ? $this->config["queue"] : $this->queue;

        foreach ($reports as $report) {

            foreach ($templates as $template) {
                if (File::exists($path . DIRECTORY_SEPARATOR . $template . DIRECTORY_SEPARATOR . $report . '.jrxml')) {

                    $input = $path . DIRECTORY_SEPARATOR . $template . DIRECTORY_SEPARATOR . $report . '.jrxml';
                    $output = $path . DIRECTORY_SEPARATOR . $template . DIRECTORY_SEPARATOR;

                    if ($queue === true) {
                        CompileReportJob::dispatch($this->systemOS(), $input, $output);
                    } else {
                        CompileReport::run($this->systemOS(), $input, $output);
                    }
                } else {
                    throw new FileCompileNotFound();
                }
            }
        }
    }

    /**
     * @throws TypeReportNotFound
     * @throws ErrorFormatReport
     * @throws FileReportNotFound
     * @throws ErrorResourcesReport
     * @throws ErrorDataAdapterReport
     * @throws Exceptions\ErrorProcessReport
     */
    public function generate(string $type, string $format, string $protect = null, array $extra = [])
    {
        $reports = $this->config["reports"];
        $formats = $this->config["formats"];
        $path = $this->config["path"];
        $path_resources = $this->config["path_resources"];
        $template = $this->config["template"];
        $dataAdapter = $this->config["connection_data_adapter"];
        $aliases = $this->config['aliases'];
        $disk = $this->config['disk_name'];
        $queue = $this->config["queue"];
        $params = $this->getParams($type, $this->config['params_global'], $this->config['params']);

        if (!File::exists(storage_path("reports"))) {
            File::makeDirectory(storage_path("reports"));
        }

        if (!array_key_exists($type, $reports)) {
            throw new TypeReportNotFound($type);
        }

        if (!in_array($format, $formats)) {
            throw new ErrorFormatReport($format);
        }

        if (!is_null($path_resources) && !File::exists($path_resources)) {
            throw new ErrorResourcesReport($path_resources);
        }

        $jrxml = $path . DIRECTORY_SEPARATOR . $template . DIRECTORY_SEPARATOR . $reports[$type] . '.jrxml';
        $jasper = $path . DIRECTORY_SEPARATOR . $template . DIRECTORY_SEPARATOR . $reports[$type] . '.jasper';

        if (!File::exists($jrxml) && !File::exists($jasper)) {
            throw new FileReportNotFound();
        }

        if (is_null($dataAdapter) && !array_key_exists($type, $this->config['reports_data_adapter'])) {
            throw new ErrorDataAdapterReport();
        }

        if (!is_null($dataAdapter)) {
            $dataAdapter = $this->configDataAdapter(config("database.connections")[$dataAdapter]);
        }

        if (!is_null($protect)) {
            $protectDefault = is_null($this->config["password_protect"]) ? $protect : $this->config["password_protect"];
            $protect = sprintf("-protect %s -protect-default %s", $protect, $protectDefault);
        } else {
            $protect = "";
        }

        $extra['nameReport'] = array_key_exists($reports[$type], $aliases) ? $aliases[$reports[$type]] : $reports[$type];
        $extra['localeReport'] = $this->getLocale();
        $extra['locale'] = app()->getLocale();

        if ($queue === true) {
            ProcessReportJob::dispatch($this->systemOS(), $reports[$type], File::exists($jasper) ? $jasper : $jrxml,
                $this->getLocale(), $format, $path_resources, $dataAdapter, $params, $protect, $disk, $extra);
        } else {
            $storage = ProcessReport::run($this->systemOS(), $reports[$type], File::exists($jasper) ? $jasper : $jrxml,
                $this->getLocale(), $format, $path_resources, $dataAdapter, $params, $protect, $disk);
            event(new ReportGenerated($storage, $extra));

        }
    }

    private function systemOS(): bool
    {
        return strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
    }

    /**
     * @throws ErrorDataAdapterReport
     */
    private function configDataAdapter($adapter): string
    {
        try {
            $replaceConfig = [
                "-t" => $adapter['driver'],
                "-H" => $adapter['host'],
                "-u" => $adapter['username'],
                "-p" => $adapter['password'],
                "-n" => $adapter['database'],
                "--db-port" => $adapter['port'],
            ];

            return implode(' ', array_map(
                function ($v, $k) {
                    if (is_array($v)) {
                        return $k . '[]=' . implode('&' . $k . '[]=', $v);
                    } else {
                        return $k . ' ' . $v;
                    }
                },
                $replaceConfig,
                array_keys($replaceConfig)));
        } catch (\Exception $exception) {
            throw new ErrorDataAdapterReport($exception);
        }
    }

    private function getLocale(): string
    {
        $locale = app()->getLocale();
        if (Str::contains($locale, "-")) {
            $locale = mb_strtolower(Str::before($locale, "-")) . "_" . mb_strtoupper(Str::after($locale, "-"));
        } else if (Str::contains($locale, "_")) {
            $locale = mb_strtolower(Str::before($locale, "_")) . "_" . mb_strtoupper(Str::after($locale, "_"));
        } else {
            $locale = mb_strtolower($locale) . "_" . mb_strtoupper($locale);
        }
        return $locale;
    }

    private function getParams(string $type, array $global, array $paramsType = []): ?string
    {
        try {
            $params_type = "";

            $global['AUTH_ID'] = auth()->check() ? auth()->id() : null;

            $paramsGlobal = implode(" ", array_map(function ($v, $k) {
                return $k . '=' . '"' . $v . '"';
            }, $global, array_keys($global)));

            if(array_key_exists($type, $paramsType)){
                $paramsType = $paramsType[$type];
                if(is_array($paramsType)){
                    $params_type = implode(" ", array_map(function ($v, $k) {
                        return $k . '=' . '"' . $v . '"';
                    }, $paramsType, array_keys($paramsType)));
                }
            }

            return sprintf("%s %s ", $paramsGlobal, $params_type);

        } catch (\Exception $exception) {

            return null;
        }
    }
}
