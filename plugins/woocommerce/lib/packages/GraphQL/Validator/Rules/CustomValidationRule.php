<?php declare(strict_types=1);

namespace Automattic\WooCommerce\Vendor\GraphQL\Validator\Rules;

use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\Node;
use Automattic\WooCommerce\Vendor\GraphQL\Language\VisitorOperation;
use Automattic\WooCommerce\Vendor\GraphQL\Validator\ValidationContext;

/**
 * @see Node, VisitorOperation
 *
 * @phpstan-type NodeVisitorFnResult VisitorOperation|mixed|null
 * @phpstan-type VisitorFnResult array<string, callable(Node): NodeVisitorFnResult>|array<string, array<string, callable(Node): NodeVisitorFnResult>>
 * @phpstan-type VisitorFn callable(ValidationContext): VisitorFnResult
 */
class CustomValidationRule extends ValidationRule
{
    /**
     * @var callable
     *
     * @phpstan-var VisitorFn
     */
    protected $visitorFn;

    /** @phpstan-param VisitorFn $visitorFn */
    public function __construct(string $name, callable $visitorFn)
    {
        $this->name = $name;
        $this->visitorFn = $visitorFn;
    }

    public function getVisitor(ValidationContext $context): array
    {
        return ($this->visitorFn)($context);
    }
}
