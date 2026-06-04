<?php declare(strict_types=1);

namespace Automattic\WooCommerce\Vendor\GraphQL\Type;

class TypeKind
{
    public const SCALAR = 'SCALAR';
    public const OBJECT = 'OBJECT';
    public const INTERFACE = 'INTERFACE';
    public const UNION = 'UNION';
    public const ENUM = 'ENUM';
    public const INPUT_OBJECT = 'INPUT_OBJECT';
    public const LIST = 'LIST';
    public const NON_NULL = 'NON_NULL';
}
