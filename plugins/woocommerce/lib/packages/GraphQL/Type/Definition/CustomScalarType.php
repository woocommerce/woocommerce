<?php declare(strict_types=1);

namespace Automattic\WooCommerce\Vendor\GraphQL\Type\Definition;

use Automattic\WooCommerce\Vendor\GraphQL\Error\Error;
use Automattic\WooCommerce\Vendor\GraphQL\Error\InvariantViolation;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\Node;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\ScalarTypeDefinitionNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\ScalarTypeExtensionNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\ValueNode;
use Automattic\WooCommerce\Vendor\GraphQL\Utils\AST;
use Automattic\WooCommerce\Vendor\GraphQL\Utils\Utils;

/**
 * @phpstan-type InputCustomScalarConfig array{
 *   name?: string|null,
 *   description?: string|null,
 *   serialize?: callable(mixed): mixed,
 *   parseValue: callable(mixed): mixed,
 *   parseLiteral: callable(ValueNode&Node, array<string, mixed>|null): mixed,
 *   astNode?: ScalarTypeDefinitionNode|null,
 *   extensionASTNodes?: array<ScalarTypeExtensionNode>|null
 * }
 * @phpstan-type OutputCustomScalarConfig array{
 *   name?: string|null,
 *   description?: string|null,
 *   serialize: callable(mixed): mixed,
 *   parseValue?: callable(mixed): mixed,
 *   parseLiteral?: callable(ValueNode&Node, array<string, mixed>|null): mixed,
 *   astNode?: ScalarTypeDefinitionNode|null,
 *   extensionASTNodes?: array<ScalarTypeExtensionNode>|null
 * }
 * @phpstan-type CustomScalarConfig InputCustomScalarConfig|OutputCustomScalarConfig
 */
class CustomScalarType extends ScalarType
{
    /** @phpstan-var CustomScalarConfig */
    // @phpstan-ignore-next-line specialize type
    public array $config;

    /**
     * @param array<string, mixed> $config
     *
     * @phpstan-param CustomScalarConfig $config
     */
    public function __construct(array $config)
    {
        parent::__construct($config);
    }

    public function serialize($value)
    {
        if (isset($this->config['serialize'])) {
            return $this->config['serialize']($value);
        }

        return $value;
    }

    public function parseValue($value)
    {
        if (isset($this->config['parseValue'])) {
            return $this->config['parseValue']($value);
        }

        return $value;
    }

    /** @throws \Exception */
    public function parseLiteral(Node $valueNode, ?array $variables = null)
    {
        if (isset($this->config['parseLiteral'])) {
            return $this->config['parseLiteral']($valueNode, $variables);
        }

        return AST::valueFromASTUntyped($valueNode, $variables);
    }

    /**
     * @throws Error
     * @throws InvariantViolation
     */
    public function assertValid(): void
    {
        parent::assertValid();

        $serialize = $this->config['serialize'] ?? null;
        $parseValue = $this->config['parseValue'] ?? null;
        $parseLiteral = $this->config['parseLiteral'] ?? null;

        $hasSerialize = $serialize !== null;
        $hasParseValue = $parseValue !== null;
        $hasParseLiteral = $parseLiteral !== null;
        $hasParse = $hasParseValue && $hasParseLiteral;

        if ($hasParseValue !== $hasParseLiteral) {
            throw new InvariantViolation("{$this->name} must provide both \"parseValue\" and \"parseLiteral\" functions to work as an input type.");
        }

        if (! $hasSerialize && ! $hasParse) {
            throw new InvariantViolation("{$this->name} must provide \"parseValue\" and \"parseLiteral\" functions, \"serialize\" function, or both.");
        }

        // @phpstan-ignore-next-line unnecessary according to types, but can happen during runtime
        if ($hasSerialize && ! is_callable($serialize)) {
            $notCallable = Utils::printSafe($serialize);
            throw new InvariantViolation("{$this->name} must provide \"serialize\" as a callable if given, but got: {$notCallable}.");
        }

        // @phpstan-ignore-next-line unnecessary according to types, but can happen during runtime
        if ($hasParseValue && ! is_callable($parseValue)) {
            $notCallable = Utils::printSafe($parseValue);
            throw new InvariantViolation("{$this->name} must provide \"parseValue\" as a callable if given, but got: {$notCallable}.");
        }

        // @phpstan-ignore-next-line unnecessary according to types, but can happen during runtime
        if ($hasParseLiteral && ! is_callable($parseLiteral)) {
            $notCallable = Utils::printSafe($parseLiteral);
            throw new InvariantViolation("{$this->name} must provide \"parseLiteral\" as a callable if given, but got: {$notCallable}.");
        }
    }
}
