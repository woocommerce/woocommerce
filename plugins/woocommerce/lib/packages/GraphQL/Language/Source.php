<?php declare(strict_types=1);

namespace Automattic\WooCommerce\Vendor\GraphQL\Language;

class Source
{
    public string $body;

    public int $length;

    public string $name;

    public SourceLocation $locationOffset;

    /**
     * A representation of source input to GraphQL.
     *
     * `name` and `locationOffset` are optional. They are useful for clients who
     * store Automattic\WooCommerce\Vendor\GraphQL documents in source files; for example, if the Automattic\WooCommerce\Vendor\GraphQL input
     * starts at line 40 in a file named Foo.graphql, it might be useful for name to
     * be "Foo.graphql" and location to be `{ line: 40, column: 0 }`.
     * line and column in locationOffset are 1-indexed
     */
    public function __construct(string $body, ?string $name = null, ?SourceLocation $location = null)
    {
        $this->body = $body;
        $this->length = mb_strlen($body, 'UTF-8');
        $this->name = $name === '' || $name === null
            ? 'Automattic\WooCommerce\Vendor\GraphQL request'
            : $name;
        $this->locationOffset = $location ?? new SourceLocation(1, 1);
    }

    public function getLocation(int $position): SourceLocation
    {
        $line = 1;
        $column = $position + 1;

        $utfChars = json_decode('"\u2028\u2029"');
        $lineRegexp = '/\r\n|[\n\r' . $utfChars . ']/su';
        $matches = [];
        preg_match_all($lineRegexp, mb_substr($this->body, 0, $position, 'UTF-8'), $matches, \PREG_OFFSET_CAPTURE);

        foreach ($matches[0] as $match) {
            ++$line;

            $column = $position + 1 - ($match[1] + mb_strlen($match[0], 'UTF-8'));
        }

        return new SourceLocation($line, $column);
    }
}
