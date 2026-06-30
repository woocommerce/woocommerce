<?php declare(strict_types=1);

namespace Automattic\WooCommerce\Vendor\GraphQL\Language;

/**
 * Represents a range of characters represented by a lexical token
 * within a Source.
 *
 * @see \Automattic\WooCommerce\Vendor\GraphQL\Tests\Language\TokenTest
 */
class Token
{
    // Each kind of token.
    public const SOF = '<SOF>';
    public const EOF = '<EOF>';
    public const BANG = '!';
    public const DOLLAR = '$';
    public const AMP = '&';
    public const PAREN_L = '(';
    public const PAREN_R = ')';
    public const SPREAD = '...';
    public const COLON = ':';
    public const EQUALS = '=';
    public const AT = '@';
    public const BRACKET_L = '[';
    public const BRACKET_R = ']';
    public const BRACE_L = '{';
    public const PIPE = '|';
    public const BRACE_R = '}';
    public const NAME = 'Name';
    public const INT = 'Int';
    public const FLOAT = 'Float';
    public const STRING = 'String';
    public const BLOCK_STRING = 'BlockString';
    public const COMMENT = 'Comment';

    /** The kind of Token (see one of constants above). */
    public string $kind;

    /** The character offset at which this Node begins. */
    public int $start;

    /** The character offset at which this Node ends. */
    public int $end;

    /** The 1-indexed line number on which this Token appears. */
    public int $line;

    /** The 1-indexed column number at which this Token begins. */
    public int $column;

    public ?string $value;

    /**
     * Tokens exist as nodes in a double-linked-list amongst all tokens
     * including ignored tokens. <SOF> is always the first node and <EOF>
     * the last.
     */
    public ?Token $prev;

    public ?Token $next = null;

    public function __construct(string $kind, int $start, int $end, int $line, int $column, ?Token $previous = null, ?string $value = null)
    {
        $this->kind = $kind;
        $this->start = $start;
        $this->end = $end;
        $this->line = $line;
        $this->column = $column;
        $this->prev = $previous;
        $this->value = $value;
    }

    public function getDescription(): string
    {
        return $this->kind
            . ($this->value === null
                ? ''
                : " \"{$this->value}\"");
    }

    /**
     * @return array{
     *   kind: string,
     *   value: string|null,
     *   line: int,
     *   column: int,
     * }
     */
    public function toArray(): array
    {
        return [
            'kind' => $this->kind,
            'value' => $this->value,
            'line' => $this->line,
            'column' => $this->column,
        ];
    }
}
