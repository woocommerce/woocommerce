<?php declare(strict_types=1);

namespace Automattic\WooCommerce\Vendor\GraphQL\Validator\Rules;

use Automattic\WooCommerce\Vendor\GraphQL\Error\Error;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\NameNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\NodeKind;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\ObjectFieldNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\Visitor;
use Automattic\WooCommerce\Vendor\GraphQL\Language\VisitorOperation;
use Automattic\WooCommerce\Vendor\GraphQL\Validator\QueryValidationContext;
use Automattic\WooCommerce\Vendor\GraphQL\Validator\SDLValidationContext;
use Automattic\WooCommerce\Vendor\GraphQL\Validator\ValidationContext;

/**
 * @phpstan-import-type VisitorArray from Visitor
 */
class UniqueInputFieldNames extends ValidationRule
{
    /** @var array<string, NameNode> */
    protected array $knownNames;

    /** @var array<array<string, NameNode>> */
    protected array $knownNameStack;

    public function getVisitor(QueryValidationContext $context): array
    {
        return $this->getASTVisitor($context);
    }

    public function getSDLVisitor(SDLValidationContext $context): array
    {
        return $this->getASTVisitor($context);
    }

    /** @phpstan-return VisitorArray */
    public function getASTVisitor(ValidationContext $context): array
    {
        $this->knownNames = [];
        $this->knownNameStack = [];

        return [
            NodeKind::OBJECT => [
                'enter' => function (): void {
                    $this->knownNameStack[] = $this->knownNames;
                    $this->knownNames = [];
                },
                'leave' => function (): void {
                    $knownNames = array_pop($this->knownNameStack);
                    assert(is_array($knownNames), 'should not happen if the visitor works correctly');

                    $this->knownNames = $knownNames;
                },
            ],
            NodeKind::OBJECT_FIELD => function (ObjectFieldNode $node) use ($context): VisitorOperation {
                $fieldName = $node->name->value;

                if (isset($this->knownNames[$fieldName])) {
                    $context->reportError(new Error(
                        static::duplicateInputFieldMessage($fieldName),
                        [$this->knownNames[$fieldName], $node->name]
                    ));
                } else {
                    $this->knownNames[$fieldName] = $node->name;
                }

                return Visitor::skipNode();
            },
        ];
    }

    public static function duplicateInputFieldMessage(string $fieldName): string
    {
        return "There can be only one input field named \"{$fieldName}\".";
    }
}
