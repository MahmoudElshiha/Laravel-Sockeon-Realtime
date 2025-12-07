<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Sockeon\Sockeon\Config\ServerConfig;
use Sockeon\Sockeon\Connection\Server;

class SockeonServeCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sockeon:serve';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Start the Sockeon WebSocket server';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $host = config('sockeon.host');
        $port = config('sockeon.port');
        $debug = config('sockeon.debug');

        // Create server configuration
        $config = new ServerConfig([
            'host' => $host,
            'port' => $port,
            'debug' => $debug
        ]);

        // Create server
        $server = new Server($config);

        // Register controllers
        $controllers = config('sockeon.controllers', []);
        foreach ($controllers as $controllerClass) {
            if (class_exists($controllerClass)) {
                $server->registerController(new $controllerClass());
                $this->info("Registered controller: {$controllerClass}");
            } else {
                $this->warn("Controller not found: {$controllerClass}");
            }
        }

        $this->info("Starting WebSocket server on ws://{$host}:{$port}");
        $this->info("Press Ctrl+C to stop");
        $this->newLine();

        // Start server
        try {
            $server->run();
        } catch (\Exception $e) {
            $this->error("Server error: " . $e->getMessage());
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
