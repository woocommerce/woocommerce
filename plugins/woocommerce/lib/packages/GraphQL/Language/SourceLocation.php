<?php declare(strict_types=1);

namespace Automattic\WooCommerce\Vendor\GraphQL\Language;

class SourceLocation implements \JsonSerializable
{
    public int $line;

    public int $column;

    public function __construct(int $line, int $col)
    {
        $this->line = $line;
        $this->column = $col;
    }

    /** @return array{line: int, column: int} */
    public function toArray(): array
    {
        return [
            'line' => $this->line,
            'column' => $this->column,
        ];
    }

    /** @return array{line: int, column: int} */
    public function toSerializableArray(): array
    {
        return $this->toArray();
    }

    /** @return array{line: int, column: int} */
    #[\ReturnTypeWillChange]
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
