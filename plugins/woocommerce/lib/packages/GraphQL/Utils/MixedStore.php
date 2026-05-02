<?php declare(strict_types=1);

namespace Automattic\WooCommerce\Vendor\GraphQL\Utils;

/**
 * Similar to PHP array, but allows any type of data to act as key (including arrays, objects, scalars).
 *
 * When storing array as key, access and modification is O(N). Avoid if possible.
 *
 * @template TValue of mixed
 *
 * @implements \ArrayAccess<mixed, TValue>
 *
 * @see \Automattic\WooCommerce\Vendor\GraphQL\Tests\Utils\MixedStoreTest
 */
class MixedStore implements \ArrayAccess
{
    /** @var array<TValue> */
    private array $standardStore = [];

    /** @var array<TValue> */
    private array $floatStore = [];

    /** @var \SplObjectStorage<object, TValue> */
    private \SplObjectStorage $objectStore;

    /** @var array<int, array<mixed>> */
    private array $arrayKeys = [];

    /** @var array<int, TValue> */
    private array $arrayValues = [];

    /** @var array<mixed> */
    private ?array $lastArrayKey = null;

    /** @var TValue|null */
    private $lastArrayValue;

    /** @var TValue|null */
    private $nullValue;

    private bool $nullValueIsSet = false;

    /** @var TValue|null */
    private $trueValue;

    private bool $trueValueIsSet = false;

    /** @var TValue|null */
    private $falseValue;

    private bool $falseValueIsSet = false;

    public function __construct()
    {
        $this->objectStore = new \SplObjectStorage();
    }

    /** @param mixed $offset */
    #[\ReturnTypeWillChange]
    public function offsetExists($offset): bool
    {
        if ($offset === false) {
            return $this->falseValueIsSet;
        }

        if ($offset === true) {
            return $this->trueValueIsSet;
        }

        if (is_int($offset) || is_string($offset)) {
            return array_key_exists($offset, $this->standardStore);
        }

        if (is_float($offset)) {
            return array_key_exists((string) $offset, $this->floatStore);
        }

        if (is_object($offset)) {
            return $this->objectStore->offsetExists($offset);
        }

        if (is_array($offset)) {
            foreach ($this->arrayKeys as $index => $entry) {
                if ($entry === $offset) {
                    $this->lastArrayKey = $offset;
                    $this->lastArrayValue = $this->arrayValues[$index];

                    return true;
                }
            }
        }

        if ($offset === null) {
            return $this->nullValueIsSet;
        }

        return false;
    }

    /**
     * @param mixed $offset
     *
     * @return TValue|null
     */
    #[\ReturnTypeWillChange]
    public function offsetGet($offset)
    {
        if ($offset === true) {
            return $this->trueValue;
        }

        if ($offset === false) {
            return $this->falseValue;
        }

        if (is_int($offset) || is_string($offset)) {
            return $this->standardStore[$offset];
        }

        if (is_float($offset)) {
            return $this->floatStore[(string) $offset];
        }

        if (is_object($offset)) {
            return $this->objectStore->offsetGet($offset);
        }

        if (is_array($offset)) {
            // offsetGet is often called directly after offsetExists, so optimize to avoid second loop:
            if ($this->lastArrayKey === $offset) {
                return $this->lastArrayValue;
            }

            foreach ($this->arrayKeys as $index => $entry) {
                if ($entry === $offset) {
                    return $this->arrayValues[$index];
                }
            }
        }

        if ($offset === null) {
            return $this->nullValue;
        }

        return null;
    }

    /**
     * @param mixed $offset
     * @param TValue $value
     *
     * @throws \InvalidArgumentException
     */
    #[\ReturnTypeWillChange]
    public function offsetSet($offset, $value): void
    {
        if ($offset === false) {
            $this->falseValue = $value;
            $this->falseValueIsSet = true;
        } elseif ($offset === true) {
            $this->trueValue = $value;
            $this->trueValueIsSet = true;
        } elseif (is_int($offset) || is_string($offset)) {
            $this->standardStore[$offset] = $value;
        } elseif (is_float($offset)) {
            $this->floatStore[(string) $offset] = $value;
        } elseif (is_object($offset)) {
            $this->objectStore[$offset] = $value;
        } elseif (is_array($offset)) {
            $this->arrayKeys[] = $offset;
            $this->arrayValues[] = $value;
        } elseif ($offset === null) {
            $this->nullValue = $value;
            $this->nullValueIsSet = true;
        } else {
            $unexpectedOffset = Utils::printSafe($offset);
            throw new \InvalidArgumentException("Unexpected offset type: {$unexpectedOffset}");
        }
    }

    /** @param mixed $offset */
    #[\ReturnTypeWillChange]
    public function offsetUnset($offset): void
    {
        if ($offset === true) {
            $this->trueValue = null;
            $this->trueValueIsSet = false;
        } elseif ($offset === false) {
            $this->falseValue = null;
            $this->falseValueIsSet = false;
        } elseif (is_int($offset) || is_string($offset)) {
            unset($this->standardStore[$offset]);
        } elseif (is_float($offset)) {
            unset($this->floatStore[(string) $offset]);
        } elseif (is_object($offset)) {
            $this->objectStore->offsetUnset($offset);
        } elseif (is_array($offset)) {
            $index = array_search($offset, $this->arrayKeys, true);

            if ($index !== false) {
                array_splice($this->arrayKeys, $index, 1);
                array_splice($this->arrayValues, $index, 1);
            }
        } elseif ($offset === null) {
            $this->nullValue = null;
            $this->nullValueIsSet = false;
        }
    }
}
