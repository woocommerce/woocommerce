<?php declare(strict_types=1);

namespace Automattic\WooCommerce\Vendor\GraphQL\Validator;

use Automattic\WooCommerce\Vendor\GraphQL\Error\Error;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\DocumentNode;
use Automattic\WooCommerce\Vendor\GraphQL\Type\Schema;

interface ValidationContext
{
    public function reportError(Error $error): void;

    /** @return list<Error> */
    public function getErrors(): array;

    public function getDocument(): DocumentNode;

    public function getSchema(): ?Schema;
}
