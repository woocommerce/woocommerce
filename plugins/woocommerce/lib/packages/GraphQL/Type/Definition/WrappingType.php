<?php declare(strict_types=1);

namespace Automattic\WooCommerce\Vendor\GraphQL\Type\Definition;

interface WrappingType
{
    /** Return the wrapped type, which may itself be a wrapping type. */
    public function getWrappedType(): Type;

    /**
     * Return the innermost wrapped type, which is guaranteed to be a named type.
     *
     * @return Type&NamedType
     */
    public function getInnermostType(): NamedType;
}
