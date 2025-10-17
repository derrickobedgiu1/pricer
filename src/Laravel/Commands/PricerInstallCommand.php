<?php

declare(strict_types=1);

namespace DerrickOb\Pricer\Laravel\Commands;

use Illuminate\Console\Command;

final class PricerInstallCommand extends Command
{
    protected $signature = 'pricer:install';

    protected $description = 'Install the Pricer package';

    public function handle(): int
    {
        $this->info('Installing Pricer...');

        $this->call('vendor:publish', [
            '--tag' => 'pricer-config',
        ]);

        $this->info('Pricer installed successfully!');
        $this->newLine();
        $this->line('Get started with:');
        $this->line("  Pricer::base(100)->tax(10, 'percent')->calculate()");

        return self::SUCCESS;
    }
}
