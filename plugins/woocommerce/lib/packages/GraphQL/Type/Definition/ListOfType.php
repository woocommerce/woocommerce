<?php declare(strict_types=1);

namespace Automattic\WooCommerce\Vendor\GraphQL\Type\Definition;

use Automattic\WooCommerce\Vendor\GraphQL\Type\Schema;

/**
 * @template-covariant OfType of Type
 */
class ListOfType extends Type implements WrappingType, OutputType, NullableType, InputType
{
    /**
     * @var Type|callable
     *
     * @phpstan-var OfType|callable(): OfType
     */
    private $wrappedType;

    /**
     * @param Type|callable $type
     *
     * @phpstan-param OfType|callable(): OfType $type
     */
    public function __construct($type)
    {
        $this->wrappedType = $type;
    }

    public function toString(): string
    {
        return '[' . $this->getWrappedType()->toString() . ']';
    }

    /** @phpstan-return OfType */
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
