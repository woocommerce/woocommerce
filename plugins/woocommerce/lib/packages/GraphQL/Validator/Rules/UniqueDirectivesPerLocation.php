<?php declare(strict_types=1);

namespace Automattic\WooCommerce\Vendor\GraphQL\Validator\Rules;

use Automattic\WooCommerce\Vendor\GraphQL\Error\Error;
use Automattic\WooCommerce\Vendor\GraphQL\Error\InvariantViolation;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\DirectiveDefinitionNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\Node;
use Automattic\WooCommerce\Vendor\GraphQL\Language\Visitor;
use Automattic\WooCommerce\Vendor\GraphQL\Type\Definition\Directive;
use Automattic\WooCommerce\Vendor\GraphQL\Validator\QueryValidationContext;
use Automattic\WooCommerce\Vendor\GraphQL\Validator\SDLValidationContext;
use Automattic\WooCommerce\Vendor\GraphQL\Validator\ValidationContext;

/**
 * Unique directive names per location.
 *
 * A Automattic\WooCommerce\Vendor\GraphQL document is only valid if all non-repeatable directives at
 * a given location are uniquely named.
 *
 * @phpstan-import-type VisitorArray from Visitor
 */
class UniqueDirectivesPerLocation extends ValidationRule
{
    /** @throws InvariantViolation */
    public function getVisitor(QueryValidationContext $context): array
    {
        return $this->getASTVisitor($context);
    }

    /** @throws InvariantViolation */
    public function getSDLVisitor(SDLValidationContext $context): array
    {
        return $this->getASTVisitor($context);
    }

    /**
     * @throws InvariantViolation
     *
     * @phpstan-return VisitorArray
     */
    public function getASTVisitor(ValidationContext $context): array
    {
        /** @var array<string, true> $uniqueDirectiveMap */
        $uniqueDirectiveMap = [];

        $schema = $context->getSchema();
        $definedDirectives = $schema !== null
            ? $schema->getDirectives()
            : Directive::getInternalDirectives();
        foreach ($definedDirectives as $directive) {
            if (! $directive->isRepeatable) {
                $uniqueDirectiveMap[$directive->name] = true;
            }
        }

        $astDefinitions = $context->getDocument()->definitions;
        foreach ($astDefinitions as $definition) {
            if ($definition instanceof DirectiveDefinitionNode
                && ! $definition->repeatable
            ) {
                $uniqueDirectiveMap[$definition->name->value] = true;
            }
        }

        return [
            'enter' => static function (Node $node) use ($uniqueDirectiveMap, $context): void {
                if (! property_exists($node, 'directives')) {
                    return;
                }

                $knownDirectives = [];

                foreach ($node->directives as $directive) {
                    $directiveName = $directive->name->value;

                    if (isset($uniqueDirectiveMap[$directiveName])) {
                        if (isset($knownDirectives[$directiveName])) {
                            $context->reportError(new Error(
                                static::duplicateDirectiveMessage($directiveName),
                                [$knownDirectives[$directiveName], $directive]
                            ));
                        } else {
                            $knownDirectives[$directiveName] = $directive;
                        }
                    }
                }
            },
        ];
    }

    public static function duplicateDirectiveMessage(string $directiveName): string
    {
        return "The directive \"{$directiveName}\" can only be used once at this location.";
    }
}
