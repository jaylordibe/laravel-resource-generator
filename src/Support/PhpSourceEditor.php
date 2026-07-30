<?php

namespace JayLordIbe\LaravelResourceGenerator\Support;

/**
 * Small, pure text edits applied to existing PHP source files.
 *
 * These replace the hard-coded character offsets the generator used previously,
 * which assumed an exact trailing file shape and broke silently when it differed.
 */
final class PhpSourceEditor
{

    /**
     * Insert a line immediately before the final closing brace of a class file.
     *
     * A blank line is kept before the closing brace, matching the project style.
     *
     * @param string $source
     * @param string $line
     *
     * @return string|null Null when the source has no closing brace.
     */
    public static function insertBeforeClosingBrace(string $source, string $line): ?string
    {
        $closingBracePosition = strrpos($source, '}');

        if ($closingBracePosition === false) {
            return null;
        }

        $body = rtrim(substr($source, 0, $closingBracePosition));
        $tail = substr($source, $closingBracePosition);

        return "{$body}\n{$line}\n\n{$tail}";
    }

    /**
     * Insert a statement immediately before the last closing of a route group.
     *
     * @param string $source
     * @param string $statement
     *
     * @return string|null Null when no route group closing was found.
     */
    public static function insertBeforeLastRouteGroupClose(string $source, string $statement): ?string
    {
        $closingGroupPosition = strrpos($source, '});');

        if ($closingGroupPosition === false) {
            return null;
        }

        return substr_replace($source, $statement, $closingGroupPosition, 0);
    }

    /**
     * Insert a use statement after the last existing one.
     *
     * Returns the source unchanged when there is no use statement to anchor to.
     *
     * @param string $source
     * @param string $useStatement
     *
     * @return string
     */
    public static function insertAfterLastUseStatement(string $source, string $useStatement): string
    {
        $lastUsePosition = strrpos($source, 'use ');

        if ($lastUsePosition === false) {
            return $source;
        }

        $endOfLinePosition = strpos($source, "\n", $lastUsePosition);

        if ($endOfLinePosition === false) {
            return $source;
        }

        return substr_replace($source, $useStatement, $endOfLinePosition + 1, 0);
    }

}
