<?php declare(strict_types=1);

namespace Automattic\WooCommerce\Vendor\GraphQL\Error;

use Automattic\WooCommerce\Vendor\GraphQL\Executor\ExecutionResult;
use Automattic\WooCommerce\Vendor\GraphQL\Language\Source;
use Automattic\WooCommerce\Vendor\GraphQL\Language\SourceLocation;
use Automattic\WooCommerce\Vendor\GraphQL\Type\Definition\Type;
use Automattic\WooCommerce\Vendor\GraphQL\Utils\Utils;
use PHPUnit\Framework\Test;

/**
 * This class is used for [default error formatting](error-handling.md).
 * It converts PHP exceptions to [spec-compliant errors](https://facebook.github.io/graphql/#sec-Errors)
 * and provides tools for error debugging.
 *
 * @see ExecutionResult
 *
 * @phpstan-import-type SerializableError from ExecutionResult
 * @phpstan-import-type ErrorFormatter from ExecutionResult
 *
 * @see \Automattic\WooCommerce\Vendor\GraphQL\Tests\Error\FormattedErrorTest
 */
class FormattedError
{
    private static string $internalErrorMessage = 'Internal server error';

    /**
     * Set default error message for internal errors formatted using createFormattedError().
     * This value can be overridden by passing 3rd argument to `createFormattedError()`.
     *
     * @api
     */
    public static function setInternalErrorMessage(string $msg): void
    {
        self::$internalErrorMessage = $msg;
    }

    /**
     * Prints a GraphQLError to a string, representing useful location information
     * about the error's position in the source.
     */
    public static function printError(Error $error): string
    {
        $printedLocations = [];

        $nodes = $error->nodes;
        if (isset($nodes) && $nodes !== []) {
            foreach ($nodes as $node) {
                $location = $node->loc;
                if (isset($location)) {
                    $source = $location->source;
                    if (isset($source)) {
                        $printedLocations[] = self::highlightSourceAtLocation(
                            $source,
                            $source->getLocation($location->start)
                        );
                    }
                }
            }
        } elseif ($error->getSource() !== null && $error->getLocations() !== []) {
            $source = $error->getSource();
            foreach ($error->getLocations() as $location) {
                $printedLocations[] = self::highlightSourceAtLocation($source, $location);
            }
        }

        return $printedLocations === []
            ? $error->getMessage()
            : implode("\n\n", array_merge([$error->getMessage()], $printedLocations)) . "\n";
    }

    /**
     * Render a helpful description of the location of the error in the Automattic\WooCommerce\Vendor\GraphQL
     * Source document.
     */
    private static function highlightSourceAtLocation(Source $source, SourceLocation $location): string
    {
        $line = $location->line;
        $lineOffset = $source->locationOffset->line - 1;
        $columnOffset = self::getColumnOffset($source, $location);
        $contextLine = $line + $lineOffset;
        $contextColumn = $location->column + $columnOffset;
        $prevLineNum = (string) ($contextLine - 1);
        $lineNum = (string) $contextLine;
        $nextLineNum = (string) ($contextLine + 1);
        $padLen = strlen($nextLineNum);

        $lines = Utils::splitLines($source->body);
        $lines[0] = self::spaces($source->locationOffset->column - 1) . $lines[0];

        $outputLines = [
            "{$source->name} ({$contextLine}:{$contextColumn})",
            $line >= 2 ? (self::leftPad($padLen, $prevLineNum) . ': ' . $lines[$line - 2]) : null,
            self::leftPad($padLen, $lineNum) . ': ' . $lines[$line - 1],
            self::spaces(2 + $padLen + $contextColumn - 1) . '^',
            $line < count($lines) ? self::leftPad($padLen, $nextLineNum) . ': ' . $lines[$line] : null,
        ];

        return implode("\n", array_filter($outputLines));
    }

    private static function getColumnOffset(Source $source, SourceLocation $location): int
    {
        return $location->line === 1
            ? $source->locationOffset->column - 1
            : 0;
    }

    private static function spaces(int $length): string
    {
        return str_repeat(' ', $length);
    }

    private static function leftPad(int $length, string $str): string
    {
        return self::spaces($length - mb_strlen($str)) . $str;
    }

    /**
     * Convert any exception to a Automattic\WooCommerce\Vendor\GraphQL spec compliant array.
     *
     * This method only exposes the exception message when the given exception
     * implements the ClientAware interface, or when debug flags are passed.
     *
     * For a list of available debug flags @see \Automattic\WooCommerce\Vendor\GraphQL\Error\DebugFlag constants.
     *
     * @return SerializableError
     *
     * @api
     */
    public static function createFromException(\Throwable $exception, int $debugFlag = DebugFlag::NONE, ?string $internalErrorMessage = null): array
    {
        $internalErrorMessage ??= self::$internalErrorMessage;

        $message = $exception instanceof ClientAware && $exception->isClientSafe()
            ? $exception->getMessage()
            : $internalErrorMessage;

        $formattedError = ['message' => $message];

        if ($exception instanceof Error) {
            $locations = array_map(
                static fn (SourceLocation $loc): array => $loc->toSerializableArray(),
                $exception->getLocations()
            );
            if ($locations !== []) {
                $formattedError['locations'] = $locations;
            }

            if ($exception->path !== null && $exception->path !== []) {
                $formattedError['path'] = $exception->path;
            }
        }

        if ($exception instanceof ProvidesExtensions) {
            $extensions = $exception->getExtensions();
            if (is_array($extensions) && $extensions !== []) {
                $formattedError['extensions'] = $extensions;
            }
        }

        if ($debugFlag !== DebugFlag::NONE) {
            $formattedError = self::addDebugEntries($formattedError, $exception, $debugFlag);
        }

        return $formattedError;
    }

    /**
     * Decorates spec-compliant $formattedError with debug entries according to $debug flags.
     *
     * @param SerializableError $formattedError
     * @param int $debugFlag For available flags @see \Automattic\WooCommerce\Vendor\GraphQL\Error\DebugFlag
     *
     * @throws \Throwable
     *
     * @return SerializableError
     */
    public static function addDebugEntries(array $formattedError, \Throwable $e, int $debugFlag): array
    {
        if ($debugFlag === DebugFlag::NONE) {
            return $formattedError;
        }

        if (($debugFlag & DebugFlag::RETHROW_INTERNAL_EXCEPTIONS) !== 0) {
            if (! $e instanceof Error) {
                throw $e;
            }

            if ($e->getPrevious() !== null) {
                throw $e->getPrevious();
            }
        }

        $isUnsafe = ! $e instanceof ClientAware || ! $e->isClientSafe();

        if (($debugFlag & DebugFlag::RETHROW_UNSAFE_EXCEPTIONS) !== 0 && $isUnsafe && $e->getPrevious() !== null) {
            throw $e->getPrevious();
        }

        if (($debugFlag & DebugFlag::INCLUDE_DEBUG_MESSAGE) !== 0 && $isUnsafe) {
            $formattedError['extensions']['debugMessage'] = $e->getMessage();
        }

        if (($debugFlag & DebugFlag::INCLUDE_TRACE) !== 0) {
            $actualError = $e->getPrevious() ?? $e;
            if ($e instanceof \ErrorException || $e instanceof \Error) {
                $formattedError['extensions']['file'] = $e->getFile();
                $formattedError['extensions']['line'] = $e->getLine();
            } else {
                $formattedError['extensions']['file'] = $actualError->getFile();
                $formattedError['extensions']['line'] = $actualError->getLine();
            }

            $isTrivial = $e instanceof Error && $e->getPrevious() === null;

            if (! $isTrivial) {
                $formattedError['extensions']['trace'] = static::toSafeTrace($actualError);
            }
        }

        return $formattedError;
    }

    /**
     * Prepares final error formatter taking in account $debug flags.
     *
     * If initial formatter is not set, FormattedError::createFromException is used.
     *
     * @phpstan-param ErrorFormatter|null $formatter
     */
    public static function prepareFormatter(?callable $formatter, int $debug): callable
    {
        return $formatter === null
            ? static fn (\Throwable $e): array => static::createFromException($e, $debug)
            : static fn (\Throwable $e): array => static::addDebugEntries($formatter($e), $e, $debug);
    }

    /**
     * Returns error trace as serializable array.
     *
     * @return array<int, array{
     *     file?: string,
     *     line?: int,
     *     function?: string,
     *     call?: string,
     * }>
     *
     * @api
     */
    public static function toSafeTrace(\Throwable $error): array
    {
        $trace = $error->getTrace();

        if (
            isset($trace[0]['function']) && isset($trace[0]['class'])
            // Remove invariant entries as they don't provide much value:
            && ($trace[0]['class'] . '::' . $trace[0]['function'] === 'Automattic\WooCommerce\Vendor\GraphQL\Utils\Utils::invariant')
        ) {
            array_shift($trace);
        } elseif (! isset($trace[0]['file'])) {
            // Remove root call as it's likely error handler trace:
            array_shift($trace);
        }

        $formatted = [];
        foreach ($trace as $err) {
            $safeErr = [];

            if (isset($err['file'])) {
                $safeErr['file'] = $err['file'];
            }

            if (isset($err['line'])) {
                $safeErr['line'] = $err['line'];
            }

            $func = $err['function'];
            $args = array_map([self::class, 'printVar'], $err['args'] ?? []);
            $funcStr = $func . '(' . implode(', ', $args) . ')';

            if (isset($err['class'])) {
                $safeErr['call'] = $err['class'] . '::' . $funcStr;
            } else {
                $safeErr['function'] = $funcStr;
            }

            $formatted[] = $safeErr;
        }

        return $formatted;
    }

    /** @param mixed $var */
    public static function printVar($var): string
    {
        if ($var instanceof Type) {
            return 'GraphQLType: ' . $var->toString();
        }

        if (is_object($var)) {
            // Calling `count` on instances of `PHPUnit\Framework\Test` triggers an unintended side effect - see https://github.com/sebastianbergmann/phpunit/issues/5866#issuecomment-2172429263
            $count = ! $var instanceof Test && $var instanceof \Countable
                ? '(' . count($var) . ')'
                : '';

            return 'instance of ' . get_class($var) . $count;
        }

        if (is_array($var)) {
            return 'array(' . count($var) . ')';
        }

        if ($var === '') {
            return '(empty string)';
        }

        if (is_string($var)) {
            return "'" . addcslashes($var, "'") . "'";
        }

        if (is_bool($var)) {
            return $var ? 'true' : 'false';
        }

        if (is_scalar($var)) {
            return (string) $var;
        }

        if ($var === null) {
            return 'null';
        }

        return gettype($var);
    }
}
