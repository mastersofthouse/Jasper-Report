<?php

namespace SoftHouse\JasperReports;

use Illuminate\Support\ServiceProvider;
use SoftHouse\JasperReports\Console\Commands\CompileReportsCommand;

class JasperReportsServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/jasper-reports.php' => config_path('jasper-reports.php'),
            ], 'config');

            $this->publishes([
                __DIR__ . '/../resources/' => resource_path(),
            ], 'assets');

            $this->commands([
                CompileReportsCommand::class,
            ]);

        }
    }

    /**
     * Register the application services.
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/jasper-reports.php', 'jasper-reports');

        ReportRouter::register();

        $this->app->singleton('jasper-reports', function () {
            return new JasperReports;
        });
    }
}
