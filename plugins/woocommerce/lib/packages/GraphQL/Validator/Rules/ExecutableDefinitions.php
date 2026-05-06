<?php declare(strict_types=1);

namespace Automattic\WooCommerce\Vendor\GraphQL\Validator\Rules;

use Automattic\WooCommerce\Vendor\GraphQL\Error\Error;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\DocumentNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\ExecutableDefinitionNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\NodeKind;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\SchemaDefinitionNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\SchemaExtensionNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\TypeDefinitionNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\TypeExtensionNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\Visitor;
use Automattic\WooCommerce\Vendor\GraphQL\Language\VisitorOperation;
use Automattic\WooCommerce\Vendor\GraphQL\Validator\QueryValidationContext;

/**
 * Executable definitions.
 *
 * A Automattic\WooCommerce\Vendor\GraphQL document is only valid for execution if all definitions are either
 * operation or fragment definitions.
 */
class ExecutableDefinitions extends ValidationRule
{
    public function getVisitor(QueryValidationContext $context): array
    {
        return [
            NodeKind::DOCUMENT => static function (DocumentNode $node) use ($context): VisitorOperation {
                foreach ($node->definitions as $definition) {
                    if (! $definition instanceof ExecutableDefinitionNode) {
                        if ($definition instanceof SchemaDefinitionNode || $definition instanceof SchemaExtensionNode) {
                            $defName = 'schema';
                        } else {
                            assert(
                                $definition instanceof TypeDefinitionNode || $definition instanceof TypeExtensionNode,
                                'only other option'
                            );
                            $defName = "\"{$definition->getName()->value}\"";
                        }

                        $context->reportError(new Error(
                            static::nonExecutableDefinitionMessage($defName),
                            [$definition]
                        ));
                    }
                }

                return Visitor::skipNode();
            },
        ];
    }

    public static function nonExecutableDefinitionMessage(string $defName): string
    {
        return "The {$defName} definition is not executable.";
    }
}
