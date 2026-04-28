<?php declare(strict_types=1);

namespace Automattic\WooCommerce\Vendor\GraphQL\Utils;

/**
 * A way to keep track of pairs of things when the ordering of the pair does
 * not matter. We do this by maintaining a sort of double adjacency sets.
 */
class PairSet
{
    /** @var array<string, array<string, bool>> */
    private array $data = [];

    public function has(string $a, string $b, bool $areMutuallyExclusive): bool
    {
        $first = $this->data[$a] ?? null;
        $result = $first !== null && isset($first[$b]) ? $first[$b] : null;
        if ($result === null) {
            return false;
        }

        // areMutuallyExclusive being false is a superset of being true,
        // hence if we want to know if this PairSet "has" these two with no
        // exclusivity, we have to ensure it was added as such.
        if ($areMutuallyExclusive === false) {
            return $result === false;
        }

        return true;
    }

    public function add(string $a, string $b, bool $areMutuallyExclusive): void
    {
        $this->pairSetAdd($a, $b, $areMutuallyExclusive);
        $this->pairSetAdd($b, $a, $areMutuallyExclusive);
    }

    private function pairSetAdd(string $a, string $b, bool $areMutuallyExclusive): void
    {
        $this->data[$a] ??= [];
        $this->data[$a][$b] = $areMutuallyExclusive;
    }
}
