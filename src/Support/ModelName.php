<?php

namespace JayLordIbe\LaravelResourceGenerator\Support;

use Illuminate\Support\Str;

/**
 * Validates the model name given on the command line and derives every casing
 * variant the stubs substitute.
 */
final class ModelName
{

    /**
     * A valid model name: starts with a letter, then letters and digits only.
     *
     * Anything else is rejected — the name becomes both a class name and a file
     * path, so separators and dots must never reach it.
     */
    private const string PATTERN = '/^[A-Za-z][A-Za-z0-9]*$/';

    /**
     * Normalise and validate a raw model name.
     *
     * @param string $modelName
     *
     * @return string|null The StudlyCase name, or null when it is unusable.
     */
    public static function resolve(string $modelName): ?string
    {
        // Whitespace only — deliberately not PHP's default charlist, which also
        // strips null bytes and would silently sanitise poisoned input into a
        // name that passes validation.
        $trimmedModelName = trim($modelName, " \t\n\r\x0B");

        if (preg_match(self::PATTERN, $trimmedModelName) !== 1) {
            return null;
        }

        return Str::studly($trimmedModelName);
    }

    /**
     * Build the stub token map for a model name.
     *
     * @param string $modelName
     *
     * @return array<string, string>
     */
    public static function tokens(string $modelName): array
    {
        $modelNamePlural = Str::plural($modelName);
        $modelNameCamelCase = Str::camel($modelName);
        $modelNameKebabCasePlural = Str::kebab($modelNamePlural);
        $modelNameSnakeCase = Str::snake($modelName);
        $modelNameSnakeCasePlural = Str::snake($modelNamePlural);
        $modelNameSpaceCase = Str::replace('_', ' ', $modelNameSnakeCase);

        return [
            '{{modelName}}' => $modelName,
            '{{modelNamePlural}}' => $modelNamePlural,
            '{{modelNameCamelCase}}' => $modelNameCamelCase,
            '{{modelNameCamelCasePlural}}' => Str::camel($modelNamePlural),
            '{{modelNameKebabCase}}' => Str::kebab($modelName),
            '{{modelNameKebabCasePlural}}' => $modelNameKebabCasePlural,
            '{{modelNameUpperKebabCasePlural}}' => Str::upper($modelNameKebabCasePlural),
            '{{modelNameSnakeCase}}' => $modelNameSnakeCase,
            '{{modelNameSnakeCasePlural}}' => $modelNameSnakeCasePlural,
            '{{modelNameUpperSnakeCasePlural}}' => Str::upper($modelNameSnakeCasePlural),
            '{{modelNameSpaceCase}}' => $modelNameSpaceCase,
            '{{modelNameSpaceCasePlural}}' => Str::replace('_', ' ', $modelNameSnakeCasePlural),
            '{{modelNameUpperWordSpaceCase}}' => ucwords($modelNameSpaceCase),
            '{{modelNameUpperFirstSpaceCase}}' => ucfirst($modelNameSpaceCase),
            '{{modelNameId}}' => "{$modelNameCamelCase}Id"
        ];
    }

    /**
     * The database table name for a model.
     *
     * @param string $modelName
     *
     * @return string
     */
    public static function tableName(string $modelName): string
    {
        return Str::lower(Str::snake(Str::plural($modelName)));
    }

    /**
     * The DatabaseTableConstant name for a model.
     *
     * @param string $modelName
     *
     * @return string
     */
    public static function tableConstantName(string $modelName): string
    {
        return Str::upper(Str::snake(Str::plural($modelName)));
    }

    /**
     * The kebab-case URL segment for a model's routes.
     *
     * @param string $modelName
     *
     * @return string
     */
    public static function routePrefix(string $modelName): string
    {
        return Str::lower(Str::kebab(Str::plural($modelName)));
    }

}
