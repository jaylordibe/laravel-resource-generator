<?php

namespace JayLordIbe\LaravelResourceGenerator\Tests\Unit;

use JayLordIbe\LaravelResourceGenerator\Support\ModelName;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ModelNameTest extends TestCase
{

    #[Test]
    public function testResolveKeepsAStudlyCaseName(): void
    {
        $this->assertSame('AppVersion', ModelName::resolve('AppVersion'));
    }

    #[Test]
    public function testResolveNormalisesLowercaseInputToStudlyCase(): void
    {
        $this->assertSame('Appversion', ModelName::resolve('appversion'));
    }

    #[Test]
    public function testResolveTrimsSurroundingWhitespace(): void
    {
        $this->assertSame('AppVersion', ModelName::resolve('  AppVersion  '));
    }

    /**
     * @param string $modelName
     */
    #[Test]
    #[DataProvider('unusableModelNameProvider')]
    public function testResolveRejectsNamesThatCannotBeAClassOrPath(string $modelName): void
    {
        $this->assertNull(ModelName::resolve($modelName));
    }

    /**
     * Names that must never reach app_path() or a class declaration.
     *
     * @return array<string, array<int, string>>
     */
    public static function unusableModelNameProvider(): array
    {
        return [
            'empty' => [''],
            'whitespace only' => ['   '],
            'path traversal' => ['../../etc/passwd'],
            'directory separator' => ['Models/AppVersion'],
            'leading digit' => ['1AppVersion'],
            'hyphen' => ['App-Version'],
            'underscore' => ['App_Version'],
            'space' => ['App Version'],
            'dot' => ['AppVersion.php'],
            'backslash' => ['App\\Version'],
            'null byte' => ["AppVersion\0"]
        ];
    }

    #[Test]
    public function testTokensCoverEveryCasingVariantForACompoundName(): void
    {
        $tokens = ModelName::tokens('AppVersion');

        $this->assertSame('AppVersion', $tokens['{{modelName}}']);
        $this->assertSame('AppVersions', $tokens['{{modelNamePlural}}']);
        $this->assertSame('appVersion', $tokens['{{modelNameCamelCase}}']);
        $this->assertSame('appVersions', $tokens['{{modelNameCamelCasePlural}}']);
        $this->assertSame('app-version', $tokens['{{modelNameKebabCase}}']);
        $this->assertSame('app-versions', $tokens['{{modelNameKebabCasePlural}}']);
        $this->assertSame('APP-VERSIONS', $tokens['{{modelNameUpperKebabCasePlural}}']);
        $this->assertSame('app_version', $tokens['{{modelNameSnakeCase}}']);
        $this->assertSame('app_versions', $tokens['{{modelNameSnakeCasePlural}}']);
        $this->assertSame('APP_VERSIONS', $tokens['{{modelNameUpperSnakeCasePlural}}']);
        $this->assertSame('app version', $tokens['{{modelNameSpaceCase}}']);
        $this->assertSame('app versions', $tokens['{{modelNameSpaceCasePlural}}']);
        $this->assertSame('App Version', $tokens['{{modelNameUpperWordSpaceCase}}']);
        $this->assertSame('App version', $tokens['{{modelNameUpperFirstSpaceCase}}']);
        $this->assertSame('appVersionId', $tokens['{{modelNameId}}']);
    }

    #[Test]
    public function testTokenKeysAreUniqueSoNoSubstitutionIsShadowed(): void
    {
        $tokens = ModelName::tokens('AppVersion');

        $this->assertCount(15, $tokens);
        $this->assertSame(array_unique(array_keys($tokens)), array_keys($tokens));
    }

    #[Test]
    public function testTokensHandleAnIrregularPlural(): void
    {
        $tokens = ModelName::tokens('Person');

        $this->assertSame('People', $tokens['{{modelNamePlural}}']);
        $this->assertSame('people', $tokens['{{modelNameSnakeCasePlural}}']);
    }

    #[Test]
    public function testTableNameIsSnakeCasePlural(): void
    {
        $this->assertSame('app_versions', ModelName::tableName('AppVersion'));
    }

    #[Test]
    public function testTableConstantNameIsUpperSnakeCasePlural(): void
    {
        $this->assertSame('APP_VERSIONS', ModelName::tableConstantName('AppVersion'));
    }

    #[Test]
    public function testRoutePrefixIsKebabCasePlural(): void
    {
        $this->assertSame('app-versions', ModelName::routePrefix('AppVersion'));
    }

}
