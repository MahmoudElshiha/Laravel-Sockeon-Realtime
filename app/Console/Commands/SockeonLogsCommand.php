<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

class SockeonLogsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sockeon:logs 
                            {--lines=50 : Number of lines to show from the end of the log file}
                            {--follow : Follow the log file in real-time}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Display and tail Sockeon WebSocket server logs';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $logPath = config('sockeon.log_file', storage_path('logs/sockeon-websocket.log'));

        if (!file_exists($logPath)) {
            $this->error("Log file not found at: {$logPath}");
            return Command::FAILURE;
        }

        $lines = $this->option('lines');
        $follow = $this->option('follow');

        if ($follow) {
            $this->info("Following Sockeon logs (Press Ctrl+C to stop)...");
            $this->newLine();
            
            // Use tail -f for real-time log following
            $process = new Process(['tail', '-f', '-n', $lines, $logPath]);
            $process->setTimeout(null);
            
            try {
                $process->run(function ($type, $buffer) {
                    echo $buffer;
                });
            } catch (\Exception $e) {
                $this->error("Error following logs: " . $e->getMessage());
                return Command::FAILURE;
            }
        } else {
            // Just show the last N lines
            $this->info("Showing last {$lines} lines of Sockeon logs:");
            $this->newLine();
            
            $process = new Process(['tail', '-n', $lines, $logPath]);
            
            try {
                $process->run(function ($type, $buffer) {
                    echo $buffer;
                });
            } catch (\Exception $e) {
                $this->error("Error reading logs: " . $e->getMessage());
                return Command::FAILURE;
            }
        }

        return Command::SUCCESS;
    }
}
