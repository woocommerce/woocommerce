<?php declare(strict_types=1);

namespace Automattic\WooCommerce\Vendor\GraphQL\Utils;

/**
 * Computes the lexical distance between strings A and B.
 *
 * The "distance" between two strings is given by counting the minimum number
 * of edits needed to transform string A into string B. An edit can be an
 * insertion, deletion, or substitution of a single character, or a swap of two
 * adjacent characters.
 *
 * Includes a custom alteration from Damerau-Levenshtein to treat case changes
 * as a single edit which helps identify mis-cased values with an edit distance
 * of 1.
 *
 * This distance can be useful for detecting typos in input or sorting
 *
 * Unlike the native levenshtein() function that always returns int, LexicalDistance::measure() returns int|null.
 * It takes into account the threshold and returns null if the measured distance is bigger.
 */
class LexicalDistance
{
    private string $input;

    private string $inputLowerCase;

    /**
     * List of char codes in the input string.
     *
     * @var array<int>
     */
    private array $inputArray;

    public function __construct(string $input)
    {
        $this->input = $input;
        $this->inputLowerCase = strtolower($input);
        $this->inputArray = self::stringToArray($this->inputLowerCase);
    }

    public function measure(string $option, float $threshold): ?int
    {
        if ($this->input === $option) {
            return 0;
        }

        $optionLowerCase = strtolower($option);

        // Any case change counts as a single edit
        if ($this->inputLowerCase === $optionLowerCase) {
            return 1;
        }

        $a = self::stringToArray($optionLowerCase);
        $b = $this->inputArray;

        if (count($a) < count($b)) {
            $tmp = $a;
            $a = $b;
            $b = $tmp;
        }

        $aLength = count($a);
        $bLength = count($b);

        if ($aLength - $bLength > $threshold) {
            return null;
        }

        /** @var array<array<int>> $rows */
        $rows = [];
        for ($i = 0; $i <= $bLength; ++$i) {
            $rows[0][$i] = $i;
        }

        for ($i = 1; $i <= $aLength; ++$i) {
            $upRow = &$rows[($i - 1) % 3];
            $currentRow = &$rows[$i % 3];

            $smallestCell = ($currentRow[0] = $i);
            for ($j = 1; $j <= $bLength; ++$j) {
                $cost = $a[$i - 1] === $b[$j - 1] ? 0 : 1;

                $currentCell = min(
                    $upRow[$j] + 1, // delete
                    $currentRow[$j - 1] + 1, // insert
                    $upRow[$j - 1] + $cost, // substitute
                );

                if ($i > 1 && $j > 1 && $a[$i - 1] === $b[$j - 2] && $a[$i - 2] === $b[$j - 1]) {
                    // transposition
                    $doubleDiagonalCell = $rows[($i - 2) % 3][$j - 2];
                    $currentCell = min($currentCell, $doubleDiagonalCell + 1);
                }

                if ($currentCell < $smallestCell) {
                    $smallestCell = $currentCell;
                }

                $currentRow[$j] = $currentCell;
            }

            // Early exit, since distance can't go smaller than smallest element of the previous row.
            if ($smallestCell > $threshold) {
                return null;
            }
        }

        $distance = $rows[$aLength % 3][$bLength];

        return $distance <= $threshold ? $distance : null;
    }

    /**
     * Returns a list of char codes in the given string.
     *
     * @return array<int>
     */
    private static function stringToArray(string $str): array
    {
        $array = [];
        foreach (mb_str_split($str) as $char) {
            $array[] = mb_ord($char);
        }

        return $array;
    }
}
