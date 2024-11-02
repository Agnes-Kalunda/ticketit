<?php

namespace Ticket\Ticketit\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class PublishRoutes extends Command
{
    protected $signature = 'ticketit:publish-routes';
    protected $description = 'Publish Ticketit routes to the application';

    public function handle()
    {
        // Source route file from package
        $sourceFile = __DIR__.'/../../routes.php';
        
        
        $routeDir = base_path('routes/ticketit');
        if (!File::isDirectory($routeDir)) {
            File::makeDirectory($routeDir, 0755, true);
        }

        
        File::copy($sourceFile, $routeDir.'/routes.php');

        // Update application's web.php
        $webRouteFile = base_path('routes/web.php');
        $includeStatement = "\n// Ticketit Routes\nrequire __DIR__.'/ticketit/routes.php';\n";

        if (!str_contains(File::get($webRouteFile), '/ticketit/routes.php')) {
            File::append($webRouteFile, $includeStatement);
        }

        $this->info('Ticketit routes published successfully!');
        $this->info('Routes file copied to: routes/ticketit/routes.php');
        $this->info('Route include statement added to: routes/web.php');
    }
}