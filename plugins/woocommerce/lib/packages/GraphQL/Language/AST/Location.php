<?php declare(strict_types=1);

namespace Automattic\WooCommerce\Vendor\GraphQL\Language\AST;

use Automattic\WooCommerce\Vendor\GraphQL\Language\Source;
use Automattic\WooCommerce\Vendor\GraphQL\Language\Token;

/**
 * Contains a range of UTF-8 character offsets and token references that
 * identify the region of the source from which the AST derived.
 *
 * @phpstan-type LocationArray array{start: int, end: int}
 */
class Location
{
    /** The character offset at which this Node begins. */
    public int $start;

    /** The character offset at which this Node ends. */
    public int $end;

    /** The Token at which this Node begins. */
    public ?Token $startToken = null;

    /** The Token at which this Node ends. */
    public ?Token $endToken = null;

    /** The Source document the AST represents. */
    public ?Source $source = null;

    public static function create(int $start, int $end): self
    {
        $tmp = new static();

        $tmp->start = $start;
        $tmp->end = $end;

        return $tmp;
    }

    public function __construct(?Token $startToken = null, ?Token $endToken = null, ?Source $source = null)
    {
        $this->startToken = $startToken;
        $this->endToken = $endToken;
        $this->source = $source;

        if ($startToken === null || $endToken === null) {
            return;
        }

        $this->start = $startToken->start;
        $this->end = $endToken->end;
    }

    /** @return LocationArray */
    public function toArray(): array
    {
        return [
            'start' => $this->start,
            'end' => $this->end,
        ];
    }
}
