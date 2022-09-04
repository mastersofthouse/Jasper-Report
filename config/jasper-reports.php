<?php

/*
 * You can place your custom package configuration in here.
 */
return [

    'disk_name' => env('DISK_REPORTS', 'public'),

    "path" => resource_path('reports'),

    "path_resources" => resource_path('reports/resources'),

    "formats" => ["pdf", "rtf", "xlsx", "docx", "pptx", "csv", "jrprint"],

    "queue" => true,

    "template" => "default",

    "templates" => [
        "default"
    ],

    "reports" => [
        "demo" => "demo"
    ],

    "aliases" => [
      "demo" => "Demo"
    ],

    "params" => [

    ],

    "params_global" => [

    ],

    "reports_data_adapter" => [

    ],

    "connection_data_adapter" => env('DB_CONNECTION', 'mysql'),

    "password_protect" => null,
];
