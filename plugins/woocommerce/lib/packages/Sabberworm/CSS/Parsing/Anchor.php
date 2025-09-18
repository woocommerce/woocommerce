<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Vendor\Sabberworm\CSS\Parsing;

/**
 * @internal since 8.7.0
 */
class Anchor
{
    /**
     * @var int<0, max>
     */
    private $position;

    /**
     * @var ParserState
     */
    private $parserState;

    /**
     * @param int<0, max> $position
     */
    public function __construct(int $position, ParserState $parserState)
    {
        $this->position = $position;
        $this->parserState = $parserState;
    }

    public function backtrack(): void
    {
        $this->parserState->setPosition($this->position);
    }
}
