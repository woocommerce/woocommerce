<?php
/**
 * Class-alias bootstrap for the Internal\Api\Schema surface.
 *
 * Some symbols in the surface — ResolveInfo and StringValueNode — cannot be
 * subclasses because the GraphQL engine constructs them itself and hands them
 * to resolver code. A subclass would be a distinct type and fail resolver
 * parameter type-hint checks. Instead we register them as class_alias of
 * their webonyx counterparts so the two FQCNs resolve to the same class.
 *
 * This file is loaded eagerly via composer's `autoload.files` entry (which
 * the Jetpack autoloader in turn exposes through its filemap), so the aliases
 * are available before any resolver is invoked.
 *
 * @internal Reserved for the GraphQL autogeneration infrastructure.
 */

declare(strict_types=1);

class_alias(
	\Automattic\WooCommerce\Vendor\GraphQL\Type\Definition\ResolveInfo::class,
	'Automattic\\WooCommerce\\Internal\\Api\\Schema\\ResolveInfo'
);

class_alias(
	\Automattic\WooCommerce\Vendor\GraphQL\Language\AST\StringValueNode::class,
	'Automattic\\WooCommerce\\Internal\\Api\\Schema\\AST\\StringValueNode'
);
