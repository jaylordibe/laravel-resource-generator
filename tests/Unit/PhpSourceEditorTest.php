<?php

namespace JayLordIbe\LaravelResourceGenerator\Tests\Unit;

use JayLordIbe\LaravelResourceGenerator\Support\PhpSourceEditor;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class PhpSourceEditorTest extends TestCase
{

    #[Test]
    public function testInsertBeforeClosingBracePlacesTheLineInsideTheClass(): void
    {
        $source = <<<'PHP'
        <?php

        class DatabaseTableConstant
        {

            const string USERS = 'users';

        }

        PHP;

        $result = PhpSourceEditor::insertBeforeClosingBrace($source, "    const string POSTS = 'posts';");

        $this->assertStringContainsString("    const string USERS = 'users';\n    const string POSTS = 'posts';\n\n}", $result);
    }

    #[Test]
    public function testInsertBeforeClosingBraceKeepsTheBlankLineBeforeTheBrace(): void
    {
        $source = "<?php\n\nclass Foo\n{\n\n    const string A = 'a';\n\n}\n";

        $result = PhpSourceEditor::insertBeforeClosingBrace($source, "    const string B = 'b';");

        $this->assertStringContainsString("const string B = 'b';\n\n}", $result);
    }

    #[Test]
    public function testInsertBeforeClosingBraceWorksWithoutATrailingBlankLine(): void
    {
        $source = "<?php\n\nclass Foo\n{\n    const string A = 'a';\n}";

        $result = PhpSourceEditor::insertBeforeClosingBrace($source, "    const string B = 'b';");

        $this->assertNotNull($result);
        $this->assertStringContainsString("const string A = 'a';\n    const string B = 'b';", $result);
    }

    #[Test]
    public function testInsertBeforeClosingBraceReturnsNullWhenThereIsNoBrace(): void
    {
        $this->assertNull(PhpSourceEditor::insertBeforeClosingBrace('<?php // nothing here', 'x'));
    }

    #[Test]
    public function testInsertBeforeLastRouteGroupCloseTargetsTheOutermostGroup(): void
    {
        $source = <<<'PHP'
        <?php

        Route::middleware('auth:api')->group(function () {
            Route::prefix('users')->group(function () {
                Route::get('/', [UserController::class, 'getPaginated']);
            });
        });

        PHP;

        $result = PhpSourceEditor::insertBeforeLastRouteGroupClose($source, "\n    // new\n");

        $this->assertNotNull($result);
        // The insert must land after the inner group closes, not inside it.
        $this->assertStringContainsString("    });\n\n    // new\n});", $result);
    }

    #[Test]
    public function testInsertBeforeLastRouteGroupCloseReturnsNullWhenNoGroupExists(): void
    {
        $this->assertNull(PhpSourceEditor::insertBeforeLastRouteGroupClose('<?php // no routes', 'x'));
    }

    #[Test]
    public function testInsertAfterLastUseStatementAppendsBelowTheExistingImports(): void
    {
        $source = "<?php\n\nuse App\\Http\\Controllers\\AController;\nuse App\\Http\\Controllers\\BController;\n\nRoute::get('/', []);\n";

        $result = PhpSourceEditor::insertAfterLastUseStatement($source, "use App\\Http\\Controllers\\CController;\n");

        $this->assertStringContainsString(
            "use App\\Http\\Controllers\\BController;\nuse App\\Http\\Controllers\\CController;\n",
            $result
        );
    }

    #[Test]
    public function testInsertAfterLastUseStatementLeavesSourceUnchangedWithoutImports(): void
    {
        $source = "<?php\n\nRoute::get('/', []);\n";

        $this->assertSame($source, PhpSourceEditor::insertAfterLastUseStatement($source, "use A;\n"));
    }

}
