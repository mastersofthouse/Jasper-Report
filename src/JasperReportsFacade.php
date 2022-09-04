<?php

namespace SoftHouse\JasperReports;

use Illuminate\Support\Facades\Facade;

class JasperReportsFacade extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'jasper-reports';
    }
}
