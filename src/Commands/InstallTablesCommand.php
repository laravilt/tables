<?php

namespace Laravilt\Tables\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class InstallTablesCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'tables:install
                            {--force : Overwrite existing files}
                            {--without-assets : Skip asset publishing}';

    /**
     * The console command description.
     */
    protected $description = 'Install Tables plugin';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Installing {{ name }} plugin...');
        $this->newLine();

        // Publish config
        $this->publishConfig();

$this->publishAssets();
        if (! $this->option('without-assets')) {
            $this->buildAssets();
        }
        $this->newLine();
        $this->info('✅ {{ name }} plugin installed successfully!');
        $this->newLine();

        return self::SUCCESS;
    }

    /**
     * Publish configuration file.
     */
    protected function publishConfig(): void
    {
        $this->info('Publishing configuration...');

        $params = ['--tag' => '{{ config }}-config'];

        if ($this->option('force')) {
            $params['--force'] = true;
        }

        Artisan::call('vendor:publish', $params, $this->output);
    }
protected function publishAssets(): void
    {
        $this->info('Publishing assets...');

        $this->call('vendor:publish', [
            '--tag' => 'tables-assets',
            '--force' => true,
        ]);

        $this->components->success('Assets published successfully!');
    }protected function buildAssets(): void
    {
        $this->info('Building assets...');

        $process = Process::path(base_path('packages/laravilt/tables'))
            ->run('npm install && npm run build');

        if ($process->successful()) {
            $this->components->success('Assets built successfully!');
        } else {
            $this->components->error('Failed to build assets: '.$process->errorOutput());
        }
    }
}
