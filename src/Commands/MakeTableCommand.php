<?php

namespace Laravilt\Tables\Commands;

use Illuminate\Console\GeneratorCommand;
use Illuminate\Support\Str;

class MakeTableCommand extends GeneratorCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:table {name : The name of the table}
                            {--actions : Include table actions}
                            {--force : Overwrite existing file}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new table class';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Table';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        parent::handle();

        $this->components->info("Table [{$this->argument('name')}] created successfully.");

        // Show usage example
        $this->newLine();
        $this->components->bulletList([
            'Import: use App\Tables\\'.str_replace('/', '\\', $this->argument('name')).';',
            'Usage: '.class_basename($this->argument('name')).'::make()->columns([...])',
        ]);
    }

    /**
     * Get the stub file for the generator.
     */
    protected function getStub(): string
    {
        if ($this->option('actions')) {
            return __DIR__.'/../../stubs/table.actions.stub';
        }

        return __DIR__.'/../../stubs/table.stub';
    }

    /**
     * Get the default namespace for the class.
     */
    protected function getDefaultNamespace($rootNamespace): string
    {
        return $rootNamespace.'\\Tables';
    }

    /**
     * Build the class with the given name.
     */
    protected function buildClass($name): string
    {
        $stub = parent::buildClass($name);

        return $this->replaceTableName($stub);
    }

    /**
     * Replace the table name in the stub.
     */
    protected function replaceTableName(string $stub): string
    {
        $name = class_basename($this->argument('name'));
        $kebabName = Str::kebab($name);
        $snakeName = Str::snake($name);

        $stub = str_replace('{{ tableKebab }}', $kebabName, $stub);
        $stub = str_replace('{{ tableSnake }}', $snakeName, $stub);

        return $stub;
    }

    /**
     * Get the destination class path.
     */
    protected function getPath($name): string
    {
        $name = Str::replaceFirst($this->rootNamespace(), '', $name);

        return $this->laravel['path'].'/'.str_replace('\\', '/', $name).'.php';
    }
}
