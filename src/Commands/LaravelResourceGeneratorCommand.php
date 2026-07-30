<?php

namespace JayLordIbe\LaravelResourceGenerator\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use JayLordIbe\LaravelResourceGenerator\Support\ModelName;
use JayLordIbe\LaravelResourceGenerator\Support\PhpSourceEditor;

class LaravelResourceGeneratorCommand extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:generate-resource {model : The StudlyCase model name, e.g. AppVersion}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate resources for a model';

    /**
     * Layers generated on every run, as stub name => path relative to the app root.
     *
     * "{{modelName}}" is substituted before the path is resolved.
     *
     * @var array<string, string>
     */
    private const array RESOURCE_LAYERS = [
        'Factory' => 'database/factories/{{modelName}}Factory.php',
        'Request' => 'app/Http/Requests/{{modelName}}Request.php',
        'Resource' => 'app/Http/Resources/{{modelName}}Resource.php',
        'Data' => 'app/Data/{{modelName}}Data.php',
        'FilterData' => 'app/Data/{{modelName}}FilterData.php',
        'UnitTest' => 'tests/Unit/{{modelName}}UnitTest.php',
        'FeatureTest' => 'tests/Feature/{{modelName}}FeatureTest.php',
        'Controller' => 'app/Http/Controllers/{{modelName}}Controller.php',
        'Service' => 'app/Services/{{modelName}}Service.php',
        'Repository' => 'app/Repositories/{{modelName}}Repository.php'
    ];

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $modelName = ModelName::resolve((string) $this->argument('model'));

        if ($modelName === null) {
            $this->error('Model name must be alphanumeric and start with a letter, e.g. "AppVersion".');

            return self::FAILURE;
        }

        $this->info(PHP_EOL . "Generating resources for {$modelName} model..." . PHP_EOL);

        // The model, its migration and its routes are created once. Re-running for an
        // existing model tops up the remaining layers without touching them.
        if (!File::exists(base_path("app/Models/{$modelName}.php"))
            && !($this->createModelFile($modelName)
                && $this->createMigrationFile($modelName)
                && $this->addRoutes($modelName))) {
            return self::FAILURE;
        }

        foreach (self::RESOURCE_LAYERS as $stubName => $relativePath) {
            if (!$this->createFileFromStub($modelName, $stubName, $relativePath)) {
                return self::FAILURE;
            }
        }

        $this->info(PHP_EOL . "Done generating resources for {$modelName} model" . PHP_EOL);

        return self::SUCCESS;
    }

    /**
     * Create the model file.
     *
     * @param string $modelName
     *
     * @return bool
     */
    private function createModelFile(string $modelName): bool
    {
        return $this->createFileFromStub($modelName, 'Model', "app/Models/{$modelName}.php");
    }

    /**
     * Create the migration file and register its table name constant.
     *
     * @param string $modelName
     *
     * @return bool
     */
    private function createMigrationFile(string $modelName): bool
    {
        $tableName = ModelName::tableName($modelName);

        // The filename carries a timestamp, so an existing migration for this table
        // can never be matched by path. Match on the table instead, or a re-run would
        // add a second create-table migration and break `php artisan migrate`.
        if ($this->migrationExistsForTable($tableName)) {
            $this->line("A migration for the {$tableName} table already exists. Skipping...");

            return $this->addDatabaseTableConstant($modelName);
        }

        $migrationFileName = date('Y_m_d_His') . "_create_{$tableName}_table.php";

        if (!$this->createFileFromStub($modelName, 'Migration', "database/migrations/{$migrationFileName}")) {
            return false;
        }

        return $this->addDatabaseTableConstant($modelName);
    }

    /**
     * Determine whether a create-table migration already exists for a table.
     *
     * @param string $tableName
     *
     * @return bool
     */
    private function migrationExistsForTable(string $tableName): bool
    {
        return !empty(File::glob(base_path("database/migrations/*_create_{$tableName}_table.php")));
    }

    /**
     * Register the table name on App\Constants\DatabaseTableConstant.
     *
     * @param string $modelName
     *
     * @return bool
     */
    private function addDatabaseTableConstant(string $modelName): bool
    {
        $path = base_path('app/Constants/DatabaseTableConstant.php');
        $fileContents = $this->readFile($path);

        if ($fileContents === null) {
            return false;
        }

        $constantName = ModelName::tableConstantName($modelName);

        if (str_contains($fileContents, " {$constantName} =")) {
            $this->line("{$constantName} is already registered in {$path}. Skipping...");

            return true;
        }

        $constant = "    const string {$constantName} = '" . ModelName::tableName($modelName) . "';";
        $updatedFileContents = PhpSourceEditor::insertBeforeClosingBrace($fileContents, $constant);

        if ($updatedFileContents === null) {
            $this->error("Could not locate the closing brace of {$path}. Add {$constantName} manually.");

            return false;
        }

        return $this->writeFile($path, $updatedFileContents, 'updated');
    }

    /**
     * Register the resource routes on routes/api.php.
     *
     * @param string $modelName
     *
     * @return bool
     */
    private function addRoutes(string $modelName): bool
    {
        $path = base_path('routes/api.php');
        $fileContents = $this->readFile($path);

        if ($fileContents === null) {
            return false;
        }

        $routePrefix = ModelName::routePrefix($modelName);

        if (str_contains($fileContents, "Route::prefix('{$routePrefix}')")) {
            $this->line("Routes for '{$routePrefix}' are already registered in {$path}. Skipping...");

            return true;
        }

        $controllerClass = "{$modelName}Controller";
        $useStatement = "use App\\Http\\Controllers\\{$controllerClass};\n";

        if (!str_contains($fileContents, $useStatement)) {
            $fileContents = PhpSourceEditor::insertAfterLastUseStatement($fileContents, $useStatement);
        }

        $routes = $this->buildRoutes($modelName, $controllerClass, $routePrefix);
        $updatedFileContents = PhpSourceEditor::insertBeforeLastRouteGroupClose($fileContents, $routes);

        if ($updatedFileContents === null) {
            $this->error("Could not locate the route group closing in {$path}. Add the {$routePrefix} routes manually.");

            return false;
        }

        return $this->writeFile($path, $updatedFileContents, 'updated');
    }

    /**
     * Build the route block for a resource.
     *
     * @param string $modelName
     * @param string $controllerClass
     * @param string $routePrefix
     *
     * @return string
     */
    private function buildRoutes(string $modelName, string $controllerClass, string $routePrefix): string
    {
        $modelIdName = lcfirst($modelName) . 'Id';
        $controllerClassName = "{$controllerClass}::class";

        return <<<ROUTES

            // {$modelName} routes
            Route::prefix('{$routePrefix}')->group(function () {
                Route::post('/', [{$controllerClassName}, 'create']);
                Route::get('/', [{$controllerClassName}, 'getPaginated']);
                Route::get('/all', [{$controllerClassName}, 'getAll']);
                Route::get('/{{$modelIdName}}', [{$controllerClassName}, 'getById'])->where('{$modelIdName}', config('custom.numeric_regex'));
                Route::put('/{{$modelIdName}}', [{$controllerClassName}, 'update'])->where('{$modelIdName}', config('custom.numeric_regex'));
                Route::delete('/{{$modelIdName}}', [{$controllerClassName}, 'delete'])->where('{$modelIdName}', config('custom.numeric_regex'));
            });

        ROUTES;
    }

    /**
     * Render a stub and write it to its destination.
     *
     * @param string $modelName
     * @param string $stubName
     * @param string $relativePath
     *
     * @return bool
     */
    private function createFileFromStub(string $modelName, string $stubName, string $relativePath): bool
    {
        $path = base_path(str_replace('{{modelName}}', $modelName, $relativePath));

        if (File::exists($path)) {
            $this->line("{$path} already exists. Skipping...");

            return true;
        }

        $stub = $this->readFile(__DIR__ . "/../Stubs/{$stubName}.stub");

        if ($stub === null) {
            return false;
        }

        $tokens = ModelName::tokens($modelName);
        $contents = str_replace(array_keys($tokens), array_values($tokens), $stub);

        File::ensureDirectoryExists(dirname($path));

        return $this->writeFile($path, $contents, 'created');
    }

    /**
     * Read a file, reporting a usable error when it is missing or unreadable.
     *
     * @param string $path
     *
     * @return string|null
     */
    private function readFile(string $path): ?string
    {
        if (!File::exists($path)) {
            $this->error("{$path} was not found.");

            return null;
        }

        $contents = file_get_contents($path);

        if ($contents === false) {
            $this->error("{$path} could not be read.");

            return null;
        }

        return $contents;
    }

    /**
     * Write a file, reporting success only when the write actually happened.
     *
     * @param string $path
     * @param string $contents
     * @param string $action
     *
     * @return bool
     */
    private function writeFile(string $path, string $contents, string $action): bool
    {
        if (file_put_contents($path, $contents) === false) {
            $this->error("{$path} could not be written.");

            return false;
        }

        $this->info("{$path} successfully {$action}" . PHP_EOL);

        return true;
    }

}
