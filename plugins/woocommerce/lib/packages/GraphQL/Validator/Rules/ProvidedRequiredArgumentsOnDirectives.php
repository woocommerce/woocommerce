<?php declare(strict_types=1);

namespace Automattic\WooCommerce\Vendor\GraphQL\Validator\Rules;

use Automattic\WooCommerce\Vendor\GraphQL\Error\Error;
use Automattic\WooCommerce\Vendor\GraphQL\Error\InvariantViolation;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\DirectiveDefinitionNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\DirectiveNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\NodeKind;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\NonNullTypeNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\Printer;
use Automattic\WooCommerce\Vendor\GraphQL\Language\Visitor;
use Automattic\WooCommerce\Vendor\GraphQL\Type\Definition\Argument;
use Automattic\WooCommerce\Vendor\GraphQL\Type\Definition\Directive;
use Automattic\WooCommerce\Vendor\GraphQL\Validator\QueryValidationContext;
use Automattic\WooCommerce\Vendor\GraphQL\Validator\SDLValidationContext;
use Automattic\WooCommerce\Vendor\GraphQL\Validator\ValidationContext;

/**
 * Provided required arguments on directives.
 *
 * A directive is only valid if all required (non-null without a
 * default value) field arguments have been provided.
 *
 * @phpstan-import-type VisitorArray from Visitor
 */
class ProvidedRequiredArgumentsOnDirectives extends ValidationRule
{
    public static function missingDirectiveArgMessage(string $directiveName, string $argName, string $type): string
    {
        return "Directive \"@{$directiveName}\" argument \"{$argName}\" of type \"{$type}\" is required but not provided.";
    }

    /** @throws \Exception */
    public function getSDLVisitor(SDLValidationContext $context): array
    {
        return $this->getASTVisitor($context);
    }

    /** @throws \Exception */
    public function getVisitor(QueryValidationContext $context): array
    {
        return $this->getASTVisitor($context);
    }

    /**
     * @throws \Exception
     * @throws \InvalidArgumentException
     * @throws \ReflectionException
     * @throws Error
     * @throws InvariantViolation
     *
     * @phpstan-return VisitorArray
     */
    public function getASTVisitor(ValidationContext $context): array
    {
        $requiredArgsMap = [];
        $schema = $context->getSchema();
        $definedDirectives = $schema === null
            ? Directive::getInternalDirectives()
            : $schema->getDirectives();

        foreach ($definedDirectives as $directive) {
            $directiveArgs = [];
            foreach ($directive->args as $arg) {
                if ($arg->isRequired()) {
                    $directiveArgs[$arg->name] = $arg;
                }
            }

            $requiredArgsMap[$directive->name] = $directiveArgs;
        }

        $astDefinition = $context->getDocument()->definitions;
        foreach ($astDefinition as $def) {
            if ($def instanceof DirectiveDefinitionNode) {
                $arguments = $def->arguments;

                $requiredArgs = [];
                foreach ($arguments as $argument) {
                    if ($argument->type instanceof NonNullTypeNode && ! isset($argument->defaultValue)) {
                        $requiredArgs[$argument->name->value] = $argument;
                    }
                }

                $requiredArgsMap[$def->name->value] = $requiredArgs;
            }
        }

        return [
            NodeKind::DIRECTIVE => [
                // Validate on leave to allow for deeper errors to appear first.
                'leave' => static function (DirectiveNode $directiveNode) use ($requiredArgsMap, $context): ?string {
                    $directiveName = $directiveNode->name->value;
                    $requiredArgs = $requiredArgsMap[$directiveName] ?? null;
                    if ($requiredArgs === null || $requiredArgs === []) {
                        return null;
                    }

                    $argNodeMap = [];
                    foreach ($directiveNode->arguments as $arg) {
                        $argNodeMap[$arg->name->value] = $arg;
                    }

                    foreach ($requiredArgs as $argName => $arg) {
                        if (! isset($argNodeMap[$argName])) {
                            $argType = $arg instanceof Argument
                                ? $arg->getType()->toString()
                                : Printer::doPrint($arg->type);

                            $context->reportError(
                                new Error(static::missingDirectiveArgMessage($directiveName, $argName, $argType), [$directiveNode])
                            );
                        }
                    }

                    return null;
                },
            ],
        ];
    }
}
