<?php declare(strict_types=1);

namespace Automattic\WooCommerce\Vendor\GraphQL\Type\Definition;

use Automattic\WooCommerce\Vendor\GraphQL\Type\Schema;

/**
 * @phpstan-type WrappedType (NullableType&Type)|callable():(NullableType&Type)
 */
class NonNull extends Type implements WrappingType, OutputType, InputType
{
    /**
     * @var Type|callable
     *
     * @phpstan-var WrappedType
     */
    private $wrappedType;

    /**
     * @param Type|callable $type
     *
     * @phpstan-param WrappedType $type
     */
    public function __construct($type)
    {
        $this->wrappedType = $type;
    }

    public function toString(): string
    {
        return $this->getWrappedType()->toString() . '!';
    }

    /** @return NullableType&Type */
    public function getWrappedType(): Type
    {
        return Schema::resolveType($this->wrappedType);
    }

    public function getInnermostType(): NamedType
    {
        $type = $this->getWrappedType();
        while ($type instanceof WrappingType) {
            $type = $type->getWrappedType();
        }

        assert($type instanceof NamedType, 'known because we unwrapped all the way down');

        return $type;
    }
}
