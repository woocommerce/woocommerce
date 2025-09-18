<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Vendor\Sabberworm\CSS\Value;

use Automattic\WooCommerce\Vendor\Sabberworm\CSS\OutputFormat;
use Automattic\WooCommerce\Vendor\Sabberworm\CSS\Parsing\ParserState;
use Automattic\WooCommerce\Vendor\Sabberworm\CSS\Parsing\SourceException;
use Automattic\WooCommerce\Vendor\Sabberworm\CSS\Parsing\UnexpectedEOFException;
use Automattic\WooCommerce\Vendor\Sabberworm\CSS\Parsing\UnexpectedTokenException;

/**
 * A `CSSFunction` represents a special kind of value that also contains a function name and where the values are the
 * function’s arguments. It also handles equals-sign-separated argument lists like `filter: alpha(opacity=90);`.
 */
class CSSFunction extends ValueList
{
    /**
     * @var non-empty-string
     *
     * @internal since 8.8.0
     */
    protected $name;

    /**
     * @param non-empty-string $name
     * @param RuleValueList|array<Value|string> $arguments
     * @param non-empty-string $separator
     * @param int<1, max>|null $lineNumber
     */
    public function __construct(string $name, $arguments, string $separator = ',', ?int $lineNumber = null)
    {
        if ($arguments instanceof RuleValueList) {
            $separator = $arguments->getListSeparator();
            $arguments = $arguments->getListComponents();
        }
        $this->name = $name;
        $this->setPosition($lineNumber); // TODO: redundant?
        parent::__construct($arguments, $separator, $lineNumber);
    }

    /**
     * @throws SourceException
     * @throws UnexpectedEOFException
     * @throws UnexpectedTokenException
     *
     * @internal since V8.8.0
     */
    public static function parse(ParserState $parserState, bool $ignoreCase = false): CSSFunction
    {
        $name = self::parseName($parserState, $ignoreCase);
        $parserState->consume('(');
        $arguments = self::parseArguments($parserState);

        $result = new CSSFunction($name, $arguments, ',', $parserState->currentLine());
        $parserState->consume(')');

        return $result;
    }

    /**
     * @throws SourceException
     * @throws UnexpectedEOFException
     * @throws UnexpectedTokenException
     */
    private static function parseName(ParserState $parserState, bool $ignoreCase = false): string
    {
        return $parserState->parseIdentifier($ignoreCase);
    }

    /**
     * @return Value|string
     *
     * @throws SourceException
     * @throws UnexpectedEOFException
     * @throws UnexpectedTokenException
     */
    private static function parseArguments(ParserState $parserState)
    {
        return Value::parseValue($parserState, ['=', ' ', ',']);
    }

    /**
     * @return non-empty-string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param non-empty-string $name
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * @return array<Value|string>
     */
    public function getArguments(): array
    {
        return $this->components;
    }

    /**
     * @return non-empty-string
     */
    public function render(OutputFormat $outputFormat): string
    {
        $arguments = parent::render($outputFormat);
        return "{$this->name}({$arguments})";
    }
}
