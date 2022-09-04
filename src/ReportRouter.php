<?php

namespace SoftHouse\JasperReports;

use Illuminate\Routing\Router;
use SoftHouse\MonitoringService\Loggly\Loggly;

class ReportRouter extends Router
{
    public static function register()
    {
        Router::macro('reports', function () {
            Router::post("/reports", function (){
                try{

                    $params = request()->all();
                    $params['auth'] = auth()->user();
                    (new JasperReports())->generate($params['model'], $params['format'], null, $params);


                    return response()->json(['success' => true], 200);
                }catch (\Exception $exception){
                    return response()->json(['success' => false], 500);
                }
            });
        });
    }
}
