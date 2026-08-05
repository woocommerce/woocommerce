<?php declare(strict_types=1);

namespace Automattic\WooCommerce\Vendor\GraphQL\Validator\Rules;

use Automattic\WooCommerce\Vendor\GraphQL\Error\Error;
use Automattic\WooCommerce\Vendor\GraphQL\Error\InvariantViolation;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\DirectiveDefinitionNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\DirectiveNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\NodeKind;
use Automattic\WooCommerce\Vendor\GraphQL\Language\Visitor;
use Automattic\WooCommerce\Vendor\GraphQL\Language\VisitorOperation;
use Automattic\WooCommerce\Vendor\GraphQL\Type\Definition\Argument;
use Automattic\WooCommerce\Vendor\GraphQL\Type\Definition\Directive;
use Automattic\WooCommerce\Vendor\GraphQL\Utils\Utils;
use Automattic\WooCommerce\Vendor\GraphQL\Validator\QueryValidationContext;
use Automattic\WooCommerce\Vendor\GraphQL\Validator\SDLValidationContext;
use Automattic\WooCommerce\Vendor\GraphQL\Validator\ValidationContext;

/**
 * Known argument names on directives.
 *
 * A Automattic\WooCommerce\Vendor\GraphQL directive is only valid if all supplied arguments are defined by
 * that field.
 *
 * @phpstan-import-type VisitorArray from Visitor
 */
class KnownArgumentNamesOnDirectives extends ValidationRule
{
    /** @param array<string> $suggestedArgs */
    public static function unknownDirectiveArgMessage(string $argName, string $directiveName, array $suggestedArgs): string
    {
        $message = "Unknown argument \"{$argName}\" on directive \"@{$directiveName}\".";

        if (isset($suggestedArgs[0])) {
            $suggestions = Utils::quotedOrList($suggestedArgs);
            $message .= " Did you mean {$suggestions}?";
        }

        return $message;
    }

    /** @throws InvariantViolation */
    public function getSDLVisitor(SDLValidationContext $context): array
    {
        return $this->getASTVisitor($context);
    }

    /** @throws InvariantViolation */
    public function getVisitor(QueryValidationContext $context): array
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
        $directiveArgs = [];
        $schema = $context->getSchema();
        $definedDirectives = $schema !== null
            ? $schema->getDirectives()
            : Directive::getInternalDirectives();

        foreach ($definedDirectives as $directive) {
            $directiveArgs[$directive->name] = array_map(
                static fn (Argument $arg): string => $arg->name,
                $directive->args
            );
        }

        $astDefinitions = $context->getDocument()->definitions;
        foreach ($astDefinitions as $def) {
            if ($def instanceof DirectiveDefinitionNode) {
                $argNames = [];
                foreach ($def->arguments as $arg) {
                    $argNames[] = $arg->name->value;
                }

                $directiveArgs[$def->name->value] = $argNames;
            }
        }

        return [
            NodeKind::DIRECTIVE => static function (DirectiveNode $directiveNode) use ($directiveArgs, $context): VisitorOperation {
                $directiveName = $directiveNode->name->value;

                if (! isset($directiveArgs[$directiveName])) {
                    return Visitor::skipNode();
                }
                $knownArgs = $directiveArgs[$directiveName];

                foreach ($directiveNode->arguments as $argNode) {
                    $argName = $argNode->name->value;
                    if (! in_array($argName, $knownArgs, true)) {
                        $suggestions = Utils::suggestionList($argName, $knownArgs);
                        $context->reportError(new Error(
                            static::unknownDirectiveArgMessage($argName, $directiveName, $suggestions),
                            [$argNode]
                        ));
                    }
                }

                return Visitor::skipNode();
            },
        ];
    }
}
