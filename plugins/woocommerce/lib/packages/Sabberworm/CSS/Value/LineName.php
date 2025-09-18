<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Vendor\Sabberworm\CSS\Value;

use Automattic\WooCommerce\Vendor\Sabberworm\CSS\OutputFormat;
use Automattic\WooCommerce\Vendor\Sabberworm\CSS\Parsing\ParserState;
use Automattic\WooCommerce\Vendor\Sabberworm\CSS\Parsing\UnexpectedEOFException;
use Automattic\WooCommerce\Vendor\Sabberworm\CSS\Parsing\UnexpectedTokenException;

class LineName extends ValueList
{
    /**
     * @param array<Value|string> $components
     * @param int<1, max>|null $lineNumber
     */
    public function __construct(array $components = [], ?int $lineNumber = null)
    {
        parent::__construct($components, ' ', $lineNumber);
    }

    /**
     * @throws UnexpectedTokenException
     * @throws UnexpectedEOFException
     *
     * @internal since V8.8.0
     */
    public static function parse(ParserState $parserState): LineName
    {
        $parserState->consume('[');
        $parserState->consumeWhiteSpace();
        $names = [];
        do {
            if ($parserState->getSettings()->usesLenientParsing()) {
                try {
                    $names[] = $parserState->parseIdentifier();
                } catch (UnexpectedTokenException $e) {
                    if (!$parserState->comes(']')) {
                        throw $e;
                    }
                }
            } else {
                $names[] = $parserState->parseIdentifier();
            }
            $parserState->consumeWhiteSpace();
        } while (!$parserState->comes(']'));
        $parserState->consume(']');
        return new LineName($names, $parserState->currentLine());
    }

    /**
     * @return non-empty-string
     */
    public function render(OutputFormat $outputFormat): string
    {
        return '[' . parent::render(OutputFormat::createCompact()) . ']';
    }
}
